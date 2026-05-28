---
description: Guidelines and architectural patterns for creating premium, high-fidelity app-like mobile interfaces and network-discoverable remote control capabilities for tools.
---


# App-Like Mobile Experience for Tools

This skill establishes the UX standards, layout guidelines, and engineering patterns required to deliver an elite, Apple-level **"App-Like Mobile Experience"** for tools running in remote control or mobile contexts.

## Activation Conditions
This skill automatically applies when you are:
- Designing or modifying UI layouts accessed on mobile devices (Android/iOS).
- Developing remote control interfaces, network discovery components, or handshake screens.
- Translating desktop-heavy control surfaces into compact, high-density, tap-ergonomic workspaces.
- Implementing local network pings, connectivity status monitors, or firewall configuration wizards.

---

## 1. The Mobile Architecture: "Remote Control Mode"
Since advanced automation engines (like `musoftware-runtime`) run natively on Windows PCs, a mobile browser cannot run them directly. We enforce a **Thin-Client Remote Control Pattern**:
- **The PC as the Server**: The Windows machine runs the runtime agent, listens on all local network interfaces (`0.0.0.0`), and exposes secure WebSocket and HTTP endpoints.
- **The Mobile Device as the Controller**: The mobile web app acts strictly as an operational control surface. It connects directly to the PC's local IP address (e.g. `192.168.1.50`) over Wi-Fi.
- **State Synchronization**: All actions, campaign progress, and real-time logs stream seamlessly to the phone via WebSockets, ensuring the phone stays fully synchronized without hosting heavy background engines.

---

## 2. Touch Ergonomics & Gestures
Mobile interfaces must NOT feel like scaled-down desktop pages. They must feel like high-performance native apps:
- **Bottom Navigation**: Use clean, bottom-anchored navigation bars or floating tabs instead of wide sidebars or crowded header dropdowns. Keep all primary actions within comfortable thumb reach.
- **Card-Based Grid System**: Never render complex multi-column data tables on small viewports. Instead, represent entities (Accounts, Campaigns, Tasks) as distinct cards with high-contrast, state-specific badges and rich visual actions.
- **Tap Ergonomics**: Ensure all interactive elements have a minimum touch target size of `44px x 44px` with comfortable padding to prevent accidental presses.
- **Floating Action Buttons (FABs)**: Use floating, prominent, bottom-right action buttons (e.g., starting a campaign, adding a contact) with sleek shadows and subtle zoom-in hover effects.

---

## 3. High-Fidelity App Aesthetics & Interactions
The user should feel like they are interacting with a premium native application:
- **Clean, Premium Light Mode**: DO NOT use dark mode. Use curated, crisp light-mode backgrounds (e.g., pure white or `slate-50` with subtle glassmorphic semi-transparent overlays) combined with bright, clear state colors (emerald for online, amber for pending, indigo for active tasks).
- **Glassmorphism**: Utilize backdrop blur filters (`backdrop-blur-md`) on cards, modals, and navigation bars to create spatial depth.
- **Micro-Animations & Physics**: Implement smooth spring-physics scale effects on button presses, fading transitions on tab switches, and pulsing glowing indicators for active network sockets.
- **Haptic Visuals**: Provide immediate, delightful visual feedback for actions (e.g., a checkmark icon scaling up with a bounce animation upon successful network linkage).

---

## 4. Local Network Discovery & Setup Guides
Connecting a mobile client to a Windows PC on the local network requires smooth, guided onboarding:
- **The Firewall Obstacle**: Windows Firewall blocks external network connections by default. We MUST provide a clean, copyable PowerShell command that opens port `18400` and `18401` as Administrator:
  ```powershell
  New-NetFirewallRule -DisplayName "Musoftware Runtime" -Direction Inbound -LocalPort 18400,18401 -Protocol TCP -Action Allow
  ```
- **Clear Instructions**: Detail the setup in three bite-sized, numbered steps using rich visuals and distinct icons (Wi-Fi, Firewall, PC Local IP).
- **IP Address Input & Validation**: Provide an easy-to-use IP input field with auto-save to `localStorage`. Validate the address locally and run a live fetch ping to verify connectivity before allowing access to the tools.
- **Persistent Link Indicator**: Render a small, sleek indicator (e.g., `Linked to PC: 192.168.1.100`) at the top of the workspace. Clicking it opens a fast-access sheet to let users edit the PC IP or disconnect.

---

## 5. Network Resiliency & Reconnection
Mobile Wi-Fi connections can be unstable. The system must adapt automatically:
- **Symmetric Reconnection**: If the WebSocket disconnects, show a quiet, top-anchored loader bar ("Reconnecting to PC...") rather than freezing the screen or showing intrusive modals.
- **State Preservation**: Store the running task states in local variables, so if the connection drops and reconnects, the interface immediately resumes rendering the correct campaign progress without losing context.

---

## 6. No Architecture Disclosure Rule (CRITICAL)
**NEVER reveal to the end user how the system works internally — not in any UI text, badges, tooltips, banners, or feature descriptions.**

On mobile interfaces in particular, it is tempting to add informational banners that explain the technology behind the tool (e.g., "Zero-Cloud Processing", "Runs on your local Node.js engine", "Files saved to your hard drive"). **These are strictly forbidden.**

Users must NOT be told:
- That processing runs locally via Node.js, a runtime agent, or any specific engine
- That files are saved to their local hard drive
- That no cloud upload occurs ("Zero-Cloud Processing")
- That a WebSocket, local IP, or network protocol is involved

Express benefits in **outcome language only**:
- **BAD**: "Zero-Cloud Processing — runs on your local machine via Node.js"
- **GOOD**: "Fast and private — your files are saved directly to your chosen folder"
- **BAD**: "Connected to PC at 192.168.1.50 via WebSocket"
- **GOOD**: "Linked to your computer" (with a simple status dot)

