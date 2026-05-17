# Real-time Chat API

The chat system is built upon a polymorphic conversation architecture, allowing secure, contextual messaging across freelance job contracts, marketplace orders, and customer support tickets. Real-time synchronization is driven by **Laravel Reverb** over WebSockets.

Base Endpoint: `/api/conversations`

## List User Conversations

`GET /api/conversations`

Retrieves a paginated list of all active conversations for the authenticated user, complete with latest message snippets, unread status, and polymorphic entity details.

## Get Conversation Details

`GET /api/conversations/{id}`

Retrieves conversation metadata, participants, read status, and unread counts.

**Response:** (`200 OK`)
```json
{
  "data": {
    "id": 5,
    "type": "freelance_contract",
    "status": "active",
    "conversable": {
      "type": "JobContract",
      "id": 12,
      "title": "Full Stack Developer - Mobile App Backend"
    },
    "participants": [
      {
        "user_id": 1,
        "name": "Ahmed Mohamed",
        "avatar": "https://api.dicebear.com/7.x/avataaars/svg?seed=Ahmed",
        "role": "client",
        "last_read_at": "2024-01-20T14:30:00Z"
      },
      {
        "user_id": 8,
        "name": "Sarah Connor",
        "avatar": "https://api.dicebear.com/7.x/avataaars/svg?seed=Sarah",
        "role": "freelancer",
        "last_read_at": "2024-01-20T14:28:00Z"
      }
    ],
    "messages_count": 24,
    "unread_count": 3,
    "created_at": "2024-01-10T09:00:00Z"
  }
}
```

## List Conversation Messages

`GET /api/conversations/{id}/messages`

**Query Parameters:**
- `page`: integer
- `per_page`: integer (`20` | `50` | `100`)
- `before_id`: integer (Used for infinite scrolling cursor pagination when scrolling upward in message history)

**Response:** (`200 OK`)
```json
{
  "data": [
    {
      "id": 100,
      "conversation_id": 5,
      "sender": {
        "id": 1,
        "name": "Ahmed Mohamed",
        "avatar": "https://api.dicebear.com/7.x/avataaars/svg?seed=Ahmed"
      },
      "body": "Hi Sarah, I've reviewed the API specifications and they look great.",
      "attachments": [
        {
          "id": 42,
          "type": "image",
          "path": "/storage/attachments/chat/spec-diagram.png",
          "mime_type": "image/png",
          "size_bytes": 1048576,
          "original_name": "architecture-diagram.png"
        }
      ],
      "created_at": "2024-01-15T10:32:00Z",
      "created_time_ago": "3 hours ago"
    }
  ],
  "meta": {
    "has_more": true,
    "oldest_id": 100
  }
}
```

## Send Message

`POST /api/conversations/{id}/messages`

**Request Body:**
```json
{
  "body": "Excellent, I will begin implementing the WebSocket channels right away.",
  "attachments": [
    {
      "file": "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=",
      "mime_type": "image/png",
      "name": "screenshot.png"
    }
  ]
}
```

**Response:** (`201 Created`)
```json
{
  "data": {
    "id": 101,
    "conversation_id": 5,
    "sender_id": 8,
    "body": "Excellent, I will begin implementing the WebSocket channels right away.",
    "attachments": [
      {
        "id": 43,
        "type": "image",
        "path": "/storage/attachments/chat/screenshot_101.png",
        "original_name": "screenshot.png"
      }
    ],
    "created_at": "2024-01-20T15:00:00Z",
    "created_time_ago": "Just now"
  }
}
```

**WebSocket Broadcast Payload Triggered:**
- **Event:** `MessageSent`
- **Channel:** `private-conversation.5`
- **Payload:** The full JSON message object shown above.

## Mark Conversation as Read

`POST /api/conversations/{id}/read`

Updates the authenticated user's `last_read_at` timestamp in `conversation_participants`.

**Response:** (`200 OK`)
```json
{
  "message": "Conversation successfully marked as read."
}
```

## WebSocket Integration Guide

Frontend consumers utilize `@laravel/echo` to listen to Reverb channels in real-time.

```javascript
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
  broadcaster: 'reverb',
  key: import.meta.env.VITE_REVERB_APP_KEY,
  wsHost: import.meta.env.VITE_REVERB_HOST,
  wsPort: import.meta.env.VITE_REVERB_PORT ?? 8080,
  wssPort: import.meta.env.VITE_REVERB_PORT ?? 8080,
  forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
  enabledTransports: ['ws', 'wss'],
});

// Listening to a private conversation channel
window.Echo.private(`conversation.${conversationId}`)
  .listen('MessageSent', (event) => {
    console.log('New message received via Reverb:', event.message);
    appendMessageToChatStore(event.message);
  })
  .listen('TypingIndicator', (event) => {
    displayTypingStatus(event.user.name);
  })
  .listenForWhisper('typing', (data) => {
    displayClientTyping(data.userName);
  });
```

### Broadcasting Whisper (Typing Indicator)
When a user types inside the message input box:
```javascript
window.Echo.private(`conversation.${conversationId}`)
  .whisper('typing', {
    userName: authUser.name,
    isTyping: true
  });
```
