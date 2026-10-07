# Linux Desktop Client Architecture

## 1. Technical Stack
- Option A: Tauri (Rust backend + Web frontend) or Electron.
- Option B: GTK4 / Libadwaita with GStreamer and PipeWire for WebRTC.

## 2. Linux Platform Features
- **Audio & Screen Capture**: PipeWire integration via WebRTC PipeWire capturer for Wayland / X11 compatibility.
- **Credential Storage**: Freedesktop Secret Service API via `libsecret` or GNOME Keyring / KWallet.
- **Notifications**: `org.freedesktop.Notifications` D-Bus interface.
- **Packaging**: AppImage, Flatpak (with portal permissions), and `.deb` / `.rpm` packages.
