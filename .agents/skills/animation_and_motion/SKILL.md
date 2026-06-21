---
name: animation_and_motion
description: Guides the purposeful use of GSAP and Framer Motion for smooth, performant micro-interactions, page transitions, and success states that do not exceed 300ms.
---

# Animation and Motion Guidelines

This skill provides guidelines and rules for implementing animations and micro-interactions within the Musoftwares application using GSAP and Framer Motion. 

## Core Principles

1. **Purposeful Animation**: Animations must have a clear purpose—to guide the user's attention, confirm actions (success states), or smooth out context switches (page transitions). Do not animate for the sake of animation.
2. **Performance First**: Animations must not jank or drop frames. Use hardware-accelerated properties (e.g., `transform`, `opacity`) and avoid animating layout properties (e.g., `width`, `height`, `margin`).
3. **Speed & Snappiness**: Keep animations smooth and snappy. **No animation should exceed 300ms in duration**, unless it's a deliberate, complex sequence (and even then, individual steps should be fast).
4. **Library Selection**:
   - Use **Framer Motion** for React-driven component states, layout animations, exit animations, and straightforward UI transitions.
   - Use **GSAP** for complex timelines, scroll-linked animations, sequence orchestrations, and entrance animations on page load where fine-grained control is needed.

## Guidelines by Context

### Micro-Interactions (Buttons, Hover States, Toggles)
- Use Framer Motion (`<motion.button>`, `<motion.div>`) or Tailwind's built-in transition classes where possible for the simplest interactions.
- Duration: 100ms - 200ms.
- Easing: Use spring physics (`type: "spring", stiffness: 400, damping: 25`) or fast ease-outs.

### Page Transitions
- Integrate with Inertia.js navigation. Use Framer Motion's `AnimatePresence` to handle component unmounting.
- Keep entry and exit simple (e.g., subtle fade and slide up).
- Duration: 200ms - 300ms.

### Success States & Modals
- When an action succeeds (e.g., form submission, checkout success), use a brief celebratory animation or smooth modal entrance.
- GSAP is excellent for chained success sequences (e.g., fading out the form, popping in a checkmark, fading in the success message).
- Duration: Do not exceed 300ms for the primary entrance.

## Code Examples

### Framer Motion: Subtle Entrance
```tsx
import { motion } from 'framer-motion';

export const FadeInContent = ({ children }) => (
  <motion.div
    initial={{ opacity: 0, y: 10 }}
    animate={{ opacity: 1, y: 0 }}
    transition={{ duration: 0.2, ease: 'easeOut' }}
  >
    {children}
  </motion.div>
);
```

### GSAP: Page Load Stagger
```tsx
import { useEffect, useRef } from 'react';
import gsap from 'gsap';

export const StaggeredList = ({ items }) => {
  const containerRef = useRef(null);

  useEffect(() => {
    gsap.fromTo(
      containerRef.current.children,
      { opacity: 0, y: 20 },
      { opacity: 1, y: 0, duration: 0.3, stagger: 0.05, ease: 'power2.out' }
    );
  }, []);

  return (
    <ul ref={containerRef}>
      {items.map(item => <li key={item.id}>{item.name}</li>)}
    </ul>
  );
};
```

---

## 🛑 STRICT INSTRUCTIONS FOR FUTURE AI AGENTS 🛑

When an AI agent is invoked to implement features, fix bugs, or make modifications related to animation, motion, or any other codebase changes, they **MUST** adhere to the following strict Git workflow:

1. **Branching Strategy**: 
   - Every feature, fix, or task MUST be isolated in its own dedicated branch. 
   - NEVER commit directly to the `main` or `master` branch.
   - Example branch names: `feature/gsap-stagger-list`, `fix/framer-motion-flicker`.

2. **Commit Frequency**: 
   - Commit changes immediately after every logical modification or functional milestone. Do not wait until the entire feature is complete to make one massive commit.
   - Keep commits small, atomic, and focused on a single responsibility.

3. **Commit Messaging**: 
   - Write clear, descriptive commit messages.
   - Format: `<type>(<scope>): <subject>` (e.g., `feat(ui): add fade-in animation to modal`, `fix(motion): reduce transition duration to 200ms`).

*Note for AI executing this specific skill creation task:* You are explicitly instructed by the user to bypass the branching rule for the creation of this `SKILL.md` file and commit it directly. Future agents reading this must follow the branching rules above.
