<?php
if (file_exists(ABSPATH . 'wp-admin/includes/class-wp-list-table.php')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}
if (!class_exists('WP_List_Table')) {
    class WP_List_Table {
        public function __construct() {}
    }
}

class QR_Trackr_List_Table extends WP_List_Table {
    private $links;
    private $post_type_filter;
    private $destination_filter;
    private $scans_filter;

    public function __construct() {
        parent::__construct([
            'singular' => 'qr_trackr_link',
            'plural'   => 'qr_trackr_links',
            'ajax'     => false
        ]);
    }

    public function get_columns() {
        return [
            'id' => 'ID',
            'post_title' => 'Post/Page',
            'destination_url' => 'Destination Link',
            'scans' => 'Scans',
            'created_at' => 'Created',
            'qr_code' => 'QR Code',
            'tracking_link' => 'Tracking Link',
            'actions' => 'Actions',
        ];
    }

    public function get_sortable_columns() {
        return [
            'id' => ['id', false],
            'post_title' => ['post_title', false],
            'destination_url' => ['destination_url', false],
            'scans' => ['scans', false],
        ];
    }

    public function prepare_items() {
        global $wpdb;
        $per_page = $this->get_items_per_page('qr_trackr_links_per_page', 20);
        $paged = $this->get_pagenum();
        $orderby = (!empty($_REQUEST['orderby'])) ? sanitize_sql_orderby($_REQUEST['orderby']) : 'id';
        $order = (!empty($_REQUEST['order'])) ? strtoupper($_REQUEST['order']) : 'DESC';
        $order = in_array($order, ['ASC', 'DESC']) ? $order : 'DESC';

        $where = 'WHERE 1=1';
        $join = '';
        $params = [];

        // Filter: post type
        if (!empty($_REQUEST['filter_post_type'])) {
            $this->post_type_filter = sanitize_text_field($_REQUEST['filter_post_type']);
            $join .= " JOIN {$wpdb->posts} p ON p.ID = l.post_id ";
            $where .= $wpdb->prepare(" AND p.post_type = %s", $this->post_type_filter);
        } else {
            $join .= " JOIN {$wpdb->posts} p ON p.ID = l.post_id ";
        }
        // Filter: post title
        if (!empty($_REQUEST['s'])) {
            $search = '%' . $wpdb->esc_like($_REQUEST['s']) . '%';
            $where .= $wpdb->prepare(" AND p.post_title LIKE %s", $search);
        }
        // Filter: destination link
        if (!empty($_REQUEST['filter_destination'])) {
            $this->destination_filter = sanitize_text_field($_REQUEST['filter_destination']);
            $where .= $wpdb->prepare(" AND l.destination_url LIKE %s", '%' . $wpdb->esc_like($this->destination_filter) . '%');
        }
        // Filter: scans (min)
        if (!empty($_REQUEST['filter_scans'])) {
            $this->scans_filter = intval($_REQUEST['filter_scans']);
            $where .= $wpdb->prepare(" AND (
                SELECT COUNT(*) FROM {$wpdb->prefix}qr_trackr_scans s WHERE s.post_id = l.post_id AND s.scan_time >= l.created_at
            ) >= %d", $this->scans_filter);
        }

        // Count total
        $total_items = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}qr_trackr_links l $join $where");

        // Main query
        $sql = "SELECT l.*, p.post_title, p.post_type,
            (
                SELECT COUNT(*) FROM {$wpdb->prefix}qr_trackr_scans s WHERE s.post_id = l.post_id AND s.scan_time >= l.created_at
            ) as scans
            FROM {$wpdb->prefix}qr_trackr_links l
            $join
            $where
            ORDER BY $orderby $order
            LIMIT %d OFFSET %d";
        $query = $wpdb->prepare($sql, $per_page, ($paged - 1) * $per_page);
        $this->links = $wpdb->get_results($query);

        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil($total_items / $per_page)
        ]);
    }

    public function column_default($item, $column_name) {
        switch ($column_name) {
            case 'id':
                return intval($item->id);
            case 'post_title':
                $edit_link = get_edit_post_link($item->post_id);
                return esc_html($item->post_title) . ($edit_link ? ' <br><a href="' . esc_url($edit_link) . '" target="_blank">Edit</a>' : '');
            case 'destination_url':
                return esc_html($item->destination_url);
            case 'scans':
                return intval($item->scans);
            case 'created_at':
                return esc_html($item->created_at);
            case 'qr_code':
                $qr_url = qr_trackr_generate_qr_image_for_link($item->id);
                return $qr_url ? '<img src="' . esc_url($qr_url) . '" style="max-width:60px; display:block; margin-bottom:4px;" alt="QR Code"><a href="' . esc_url($qr_url) . '" download class="button">Download</a>' : '';
            case 'tracking_link':
                $tracking_link = trailingslashit(home_url()) . 'qr-trackr/redirect/' . intval($item->id);
                return '<a href="' . esc_url($tracking_link) . '" target="_blank">' . esc_html($tracking_link) . '</a>';
            case 'actions':
                return '<a href="#" class="qr-trackr-update-link" data-link-id="' . intval($item->id) . '">Update Destination</a>';
            default:
                return '';
        }
    }

    public function display_rows() {
        foreach ($this->items as $item) {
            echo '<tr>';
            foreach ($this->get_columns() as $column_name => $column_display_name) {
                echo '<td>' . $this->column_default($item, $column_name) . '</td>';
            }
            echo '</tr>';
            // Expandable update row
            echo '<tr class="qr-trackr-update-row" id="qr-trackr-update-row-' . intval($item->id) . '" style="display:none;">';
            echo '<td colspan="' . count($this->get_columns()) . '">';
            echo '<form method="post" class="qr-trackr-update-form">';
            wp_nonce_field('qr_trackr_admin_update_dest_' . $item->id, 'qr_trackr_admin_dest_nonce');
            echo '<input type="url" name="qr_trackr_admin_dest_url" value="' . esc_attr($item->destination_url) . '" style="width:60%;max-width:400px;" required> ';
            echo '<input type="hidden" name="qr_trackr_admin_link_id" value="' . intval($item->id) . '">';
            echo '<button type="submit" class="button button-primary">Update</button>';
            echo '<button type="button" class="button qr-trackr-cancel-update" data-link-id="' . intval($item->id) . '">Cancel</button>';
            echo '</form>';
            echo '</td>';
            echo '</tr>';
        }
    }

    public function extra_tablenav($which) {
        if ($which === 'top') {
            // Post type filter
            $selected = isset($_REQUEST['filter_post_type']) ? esc_attr($_REQUEST['filter_post_type']) : '';
            echo '<div class="alignleft actions">';
            echo '<select name="filter_post_type">';
            echo '<option value="">All Types</option>';
            foreach (get_post_types(['public' => true], 'objects') as $type) {
                echo '<option value="' . esc_attr($type->name) . '"' . selected($selected, $type->name, false) . '>' . esc_html($type->labels->singular_name) . '</option>';
            }
            echo '</select>';
            // Destination link filter
            $dest = isset($_REQUEST['filter_destination']) ? esc_attr($_REQUEST['filter_destination']) : '';
            echo '<input type="text" name="filter_destination" placeholder="Destination Link" value="' . $dest . '" style="width:140px;" />';
            // Scans filter
            $scans = isset($_REQUEST['filter_scans']) ? esc_attr($_REQUEST['filter_scans']) : '';
            echo '<input type="number" name="filter_scans" placeholder="Min Scans" value="' . $scans . '" style="width:100px;" min="0" />';
            echo '<input type="submit" class="button" value="Filter">';
            echo '</div>';
        }
    }
} 