export interface Greeting {
    key: 'morning' | 'afternoon' | 'evening' | 'night';
}

export function getGreeting(date: Date = new Date()): Greeting {
    const hour = date.getHours();

    if (hour >= 5 && hour < 12) return { key: 'morning' };
    if (hour >= 12 && hour < 17) return { key: 'afternoon' };
    if (hour >= 17 && hour < 22) return { key: 'evening' };
    return { key: 'night' };
}

export const GREETING_KEY = 'general.greeting_';
