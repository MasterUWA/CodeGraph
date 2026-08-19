#!/bin/bash

# Colors for output
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${BLUE}🚀 Starting CodeGraph Server${NC}"
echo "================================"

# Check if vendor exists
if [ ! -d "vendor" ]; then
    echo -e "${YELLOW}📦 Vendor folder not found. Running setup...${NC}"
    bash setup.sh
fi

# Create uploads directory if it doesn't exist
if [ ! -d "uploads" ]; then
    mkdir -p uploads
    chmod 777 uploads
fi

# Get local IP
if command -v ifconfig &> /dev/null; then
    LOCAL_IP=$(ifconfig | grep "inet " | grep -v 127.0.0.1 | awk '{print $2}' | head -n 1)
elif command -v ip &> /dev/null; then
    LOCAL_IP=$(ip addr show | grep "inet " | grep -v 127.0.0.1 | awk '{print $2}' | cut -d/ -f1 | head -n 1)
else
    LOCAL_IP="localhost"
fi

echo ""
echo -e "${GREEN}✅ Server starting...${NC}"
echo ""
echo -e "📝 Local access:    ${BLUE}http://localhost:8080${NC}"
echo -e "📝 Network access:  ${BLUE}http://$LOCAL_IP:8080${NC}"
echo ""
echo -e "${YELLOW}🛑 Press Ctrl+C to stop the server${NC}"
echo ""

# Start PHP built-in server
php -S 0.0.0.0:8080 -t public/