---
name: SEO Best Practices
description: Enforces Search Engine Optimization (SEO) standards for website archetypes.
---

# SEO Best Practices

When building pages, views, or layouts for a website, you must ensure they are fully optimized for search engines.

## Core Rules

1. **Semantic HTML**: Use proper HTML5 tags (`<header>`, `<nav>`, `<main>`, `<article>`, `<section>`, `<footer>`) instead of generic `<div>` tags wherever possible.
2. **Heading Hierarchy**: 
   - Every page MUST have exactly one `<h1>` tag describing the main topic of the page.
   - Use headings (`<h2>`, `<h3>`, etc.) sequentially to structure content. Do not skip heading levels.
3. **Meta Tags**: 
   - Ensure a dynamic `<title>` tag exists and accurately reflects the page content (e.g., `Page Name - App Name`).
   - Include a `<meta name="description" content="...">` for every page.
   - For interactive frameworks (like Next.js), use the built-in Metadata API. For Laravel, yield the title/description into the main layout.
4. **Image Alt Text**: Every `<img>` tag must have an `alt` attribute describing the image for search engines and screen readers.
5. **Canonical URLs**: When appropriate, include `<link rel="canonical" href="...">` to prevent duplicate content issues.
6. **Open Graph Tags**: Include basic Open Graph tags (`og:title`, `og:description`, `og:image`) for better social sharing previews.
