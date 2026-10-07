# iOS Native Client Architecture

## 1. Technical Stack
- **Language**: Swift 6.0+
- **UI Framework**: SwiftUI + Combine
- **Networking**: URLSession / Alamofire
- **Local Persistence**: SwiftData / CoreData SQLite store
- **Calling / VoIP**: CallKit framework + WebRTC iOS framework (`WebRTC.framework`)
- **Push**: Apple Push Notification service (APNs) + VoIP Push Notifications (`PushKit`)
- **Background Handling**: `BGAppRefreshTask` and `BGProcessingTask`
- **Security**: Apple Keychain Services with `kSecAttrAccessibleAfterFirstUnlockThisDeviceOnly`

## 2. CallKit Integration
- Uses `CXProvider` to report incoming calls from APNs VoIP payload directly to native iOS system call screen.
- Supports answering or declining from lock screen.
- Routes audio session using `AVAudioSession.sharedInstance()` in `.playAndRecord` mode with `.voiceChat` mode.

## 3. Universal Links
- Associated Domains (`applinks:jugajug.com`) to handle universal links and deep links directly into conversation or active call.
