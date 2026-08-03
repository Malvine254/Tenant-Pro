#!/bin/bash
# Update Backend IP Script (macOS/Linux)
# auto mode uses emulator localhost when an emulator is connected; otherwise it
# detects the active LAN IP for a physical Android device.

TARGET="${1:-auto}"

if [ "$TARGET" = "auto" ]; then
    TARGET="device"
    if command -v adb >/dev/null 2>&1; then
        ADB_DEVICES="$(adb devices 2>/dev/null)"
        if echo "$ADB_DEVICES" | grep -Eq '^emulator-[0-9]+[[:space:]]+device' &&
           ! echo "$ADB_DEVICES" | grep -Ev '^List of devices attached|^$|^emulator-' | grep -Eq '[[:space:]]device$'; then
            TARGET="emulator"
        fi
    fi
fi

if [ "$TARGET" = "emulator" ]; then
    NETWORK_IP="10.0.2.2"
    echo "✅ Using emulator host loopback mapping: $NETWORK_IP"
    if command -v adb >/dev/null 2>&1; then
        if adb reverse tcp:3000 tcp:3000 >/dev/null 2>&1; then
            echo "✅ ADB reverse enabled for tcp:3000"
        else
            echo "⚠️  ADB reverse unavailable (continuing with 10.0.2.2 fallback)."
        fi
    fi
else
    echo "🔍 Detecting your network IP address for physical device..."

    if [[ "$OSTYPE" == "darwin"* ]]; then
        NETWORK_IP=$(ipconfig getifaddr en0 2>/dev/null || ipconfig getifaddr en1 2>/dev/null)
    else
        NETWORK_IP=$(ip route get 1.1.1.1 2>/dev/null | awk '{for (i=1; i<=NF; i++) if ($i=="src") print $(i+1)}' | head -n 1)
        if [ -z "$NETWORK_IP" ]; then
            NETWORK_IP=$(hostname -I | awk '{print $1}')
        fi
    fi

    if [ -z "$NETWORK_IP" ]; then
        echo "❌ Could not detect network IP address. Make sure you're connected to a network."
        exit 1
    fi

    echo "✅ Detected IP: $NETWORK_IP"
fi

# Update local.properties
LOCAL_PROPS="$(dirname "$0")/local.properties"

if [ -f "$LOCAL_PROPS" ]; then
    # Check if backend.host exists
    if grep -q "^backend.host=" "$LOCAL_PROPS"; then
        # Update existing line
        sed -i.bak "s/^backend.host=.*/backend.host=$NETWORK_IP/" "$LOCAL_PROPS"
        rm "${LOCAL_PROPS}.bak" 2>/dev/null
    else
        # Add new configuration
        echo "" >> "$LOCAL_PROPS"
        echo "# Backend API Configuration" >> "$LOCAL_PROPS"
        echo "# The app will automatically use this IP address to connect to your backend" >> "$LOCAL_PROPS"
        echo "backend.host=$NETWORK_IP" >> "$LOCAL_PROPS"
        echo "backend.port=3000" >> "$LOCAL_PROPS"
    fi

    if grep -q "^backend.baseUrl=" "$LOCAL_PROPS"; then
        sed -i.bak "s|^backend.baseUrl=.*|backend.baseUrl=http\\://$NETWORK_IP\\:3000/api/|" "$LOCAL_PROPS"
        rm "${LOCAL_PROPS}.bak" 2>/dev/null
    else
        echo "backend.baseUrl=http\\://$NETWORK_IP\\:3000/api/" >> "$LOCAL_PROPS"
    fi
    
    echo "✅ Updated local.properties with IP: $NETWORK_IP"
    echo ""
    echo "📱 Next steps:"
    echo "   1. In Android Studio, click: File → Sync Project with Gradle Files"
    echo "   2. Rebuild and run your app"
    echo ""
    echo "🌐 Your app will now connect to: http://$NETWORK_IP:3000/api/"
else
    echo "❌ local.properties file not found at: $LOCAL_PROPS"
    exit 1
fi
