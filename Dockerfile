# Use the official WordPress image
FROM wordpress:latest

# Set recommended PHP settings for development
RUN echo "upload_max_filesize=64M" > /usr/local/etc/php/conf.d/uploads.ini \
    && echo "post_max_size=64M" >> /usr/local/etc/php/conf.d/uploads.ini

# Expose the default WordPress port
EXPOSE 80

# The plugin should be mounted via a volume or copied in via docker-compose
# Environment variables are configured via docker-compose or .env file 