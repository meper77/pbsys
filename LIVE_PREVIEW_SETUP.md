# Android APK Live Preview Setup

This guide helps you set up Flutter's hot reload/hot restart for instant Android app updates during development.

## 📋 Prerequisites

- Flutter SDK installed and in PATH
- Android SDK/Android Studio installed
- One of the following:
  - Android emulator running
  - Physical Android device connected via USB with USB debugging enabled

## 🚀 Quick Start

### 1. Check Connected Devices

```bash
flutter devices
```

You should see your emulator or device listed.

### 2. Start Live Preview

Navigate to your Flutter project directory and run:

```bash
cd pbsystem_app
flutter run -d <device-id>
```

Replace `<device-id>` with the device ID from the `flutter devices` command.

**For the default/only device:**
```bash
flutter run
```

## 🔄 Hot Reload vs Hot Restart

### Hot Reload (Faster)
- **Keyboard Shortcut:** `r` (when app is running)
- **What it does:** Recompiles Dart code and updates the UI instantly
- **Preserves:** App state, navigation stack
- **Use when:** Changing UI, fixing bugs in widgets, testing styles
- ⚡ Takes ~1-2 seconds

### Hot Restart (Full Restart)
- **Keyboard Shortcut:** `R` (when app is running)
- **What it does:** Full app restart, recompiles everything
- **Resets:** App state (as if you closed and reopened the app)
- **Use when:** Adding new routes, changing state initialization, database changes
- ⏱️ Takes ~3-5 seconds

## 🎮 Live Preview Commands

When `flutter run` is active, you can use:

| Key | Action |
|-----|--------|
| `r` | Hot reload (fast update) |
| `R` | Hot restart (full restart) |
| `h` | Display help |
| `w` | Dump widget hierarchy to console |
| `t` | Dump render tree to console |
| `L` | Dump layer tree to console |
| `S` | Toggle stats (performance overlay) |
| `U` | Toggle WidgetInspector |
| `i` | Toggle platform overrides |
| `p` | Toggle performance overlay |
| `M` | Print memory usage |
| `q` | Quit |

## 📱 Auto-Reload on File Change

For **even faster** workflow, run with automatic reload on code changes:

```bash
flutter run --verbose
```

This will automatically trigger hot reload whenever you save a file in your project.

## 🖥️ Android Emulator Setup

If you don't have an emulator running, start one:

```bash
# List available emulators
emulator -list-avds

# Start an emulator
emulator -avd <emulator-name>
```

Or use Android Studio's AVD Manager.

## 🔗 USB Device Setup

1. Enable Developer Mode on your Android device
   - Go to Settings → About Phone
   - Tap Build Number 7 times
   - Go back and find Developer Options

2. Enable USB Debugging in Developer Options

3. Connect device via USB cable

4. Verify connection:
```bash
flutter devices
```

## 🐛 Troubleshooting

### Flutter not found
```bash
add your Flutter bin directory to PATH
# Example (macOS/Linux):
export PATH="$PATH:/path/to/flutter/bin"
```

### Device not detected
```bash
# Reload device driver
flutter devices

# For USB device, check connection
adb devices
```

### Hot reload not working
- Try hot restart (`R`) instead
- Check console for compilation errors
- Restart the emulator/device
- Run `flutter clean` and try again

## 📊 Performance Monitoring

While app is running, press `p` to toggle the performance overlay. This shows:
- Mobile GPU & CPU performance graphs
- Frame rate
- Rendering issues

## 🚨 What Breaks Hot Reload

Hot reload won't update:
- Main function changes
- Global variables
- Static initializers
- App metadata (AndroidManifest.xml)

In these cases, use hot restart (`R`) or rebuild.

## 🎯 Pro Tips

1. **Split your screen:** Run the app on left side, code editor on right
2. **Use DevTools:** Run `flutter pub global activate devtools` then `devtools`
3. **Check logs:** `flutter logs` to see device logs in real-time
4. **Run tests:** `flutter test` for unit tests

## 📚 Next Steps

- Check `flutter run --help` for all options
- Visit https://flutter.dev/docs/development/tools/hot-reload
- Explore Flutter DevTools: `flutter pub global run devtools`
