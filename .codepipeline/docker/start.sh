# Configure nginx
# If REDIRECT_HTTP_TO_HTTPS is set, add the redirect to nginx.conf
if [ "$REDIRECT_HTTP_TO_HTTPS" = true ]; then
    echo "Configuring nginx to redirect HTTP to HTTPS"
    NGINX_CONF="/etc/nginx/conf.d/default.conf"
    # Remove lines that contain "listen 80" from the nginx.conf (and ipv6)
    sed -i '/listen \[::\]:80/d' $NGINX_CONF
    sed -i '/listen 80/d' $NGINX_CONF
    # Add a new server block to redirect HTTP to HTTPS
    if [ "$REDIRECT_HOST" = "" ]; then
        echo "REDIRECT_HOST is not set, using default value"
        REDIRECT_HOST="\$host"
    fi
    echo "
    # Redirect HTTP to HTTPS
    server {
        listen 80;
        listen [::]:80;
        server_name localhost;
        return 301 https://$REDIRECT_HOST\$request_uri;
    }
    # End - Redirect HTTP to HTTPS
    " >> $NGINX_CONF
fi

# Start nginx
echo "Starting nginx"
supervisorctl start nginx
if [ $? -ne 0 ]; then
    echo "Failed to start nginx"
    exit 1
fi

# Run setup.sh as cmfive user
echo "Running setup"
supervisorctl start cmfive-setup
if [ $? -ne 0 ]; then
    echo "Failed to run setup.sh"
    exit 1
fi
