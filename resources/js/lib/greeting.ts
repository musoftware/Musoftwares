export interface Greeting {
    key: 'morning' | 'afternoon' | 'evening' | 'night';
    emoji: string;
}

export function getGreeting(date: Date = new Date()): Greeting {
    const hour = date.getHours();

    if (hour >= 5 && hour < 12) {
        return { key: 'morning', emoji: '☕️' };
    }
    if (hour >= 12 && hour < 17) {
        return { key: 'afternoon', emoji: '☀️' };
    }
    if (hour >= 17 && hour < 22) {
        return { key: 'evening', emoji: '🌙' };
    }
    return { key: 'night', emoji: '🌙' };
}

export const GREETING_KEY = 'general.greeting_';
