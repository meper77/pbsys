# 🚀 Flutter Live Preview - Quick Reference

## Start Live Preview Now

### Option 1: Using the script
```bash
cd pbsystem_app
./run-live-preview.sh
```

### Option 2: Direct command
```bash
cd pbsystem_app
flutter run
```

### Option 3: Using dev menu
```bash
cd pbsystem_app
./flutter-dev-menu.sh
```

---

## ⌨️ Live Preview Keyboard Shortcuts

While `flutter run` is active, press:

| Key | Description |
|-----|-------------|
| **r** | 🔄 Hot Reload - Update UI instantly (fastest) |
| **R** | 🔁 Hot Restart - Full app restart |
| **h** | ❓ Show all commands |
| **w** | 📊 Widget tree hierarchy |
| **t** | 🌳 Render tree |
| **L** | 📐 Layer tree |
| **S** | 📈 Performance stats overlay |
| **U** | 🔍 Widget inspector |
| **i** | ⚙️ Platform overrides |
| **p** | 📊 Performance overlay |
| **M** | 💾 Memory usage |
| **q** | ❌ Quit app (stops flutter run) |

---

## 🎯 Workflow Examples

### Editing UI/Styles
1. Save your code change
2. Press `r` for hot reload
3. See changes immediately ⚡

### Adding a new screen/route
1. Create your new widget
2. Press `R` for hot restart
3. Navigate to new screen ✓

### Debugging state issues
1. Press `S` to toggle stats
2. Press `U` to open widget inspector
3. Tap widgets to inspect state
4. Make code changes, press `r`

---

## 📱 Device Setup

### Android Emulator
```bash
# List emulators
emulator -list-avds

# Start emulator
emulator -avd Pixel_4_API_30

# Or use Android Studio's AVD Manager
```

### Physical Device
1. Enable USB Debugging in Dev Options
2. Connect via USB
3. Run `flutter devices` to verify

---

## 🧰 Useful Commands

```bash
# Check devices
flutter devices

# View real-time logs
flutter logs

# Check environment
flutter doctor

# Open DevTools (advanced debugging)
flutter pub global activate devtools
flutter pub global run devtools
```

---

## 📊 Performance Tips

- Use `S` key to monitor FPS and detect jank
- Use `U` key + tap widgets to inspect state
- Use `M` key to check memory usage
- Look for red squares in performance overlay (60 FPS threshold)

---

## 🐛 If Hot Reload Doesn't Work

1. **Try hot restart** (`R` instead of `r`)
2. **Check logs** for compilation errors
3. **Restart emulator** and try again
4. **Run `flutter clean`** then `flutter run`

**Hot reload can't update:**
- Main function changes
- Global variables
- AndroidManifest.xml changes
→ Use hot restart (`R`) instead

---

## 📚 Learn More

- **Official Docs:** https://flutter.dev/docs/development/tools/hot-reload
- **Dart Docs:** https://dart.dev
- **Flutter DevTools:** https://flutter.dev/docs/development/tools/devtools

---

**💡 Pro Tip:** Split your desktop! Run the app on one side, VS Code on the other for maximum efficiency.
