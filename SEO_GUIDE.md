# SEO Integration Guide

A consistent, framework-agnostic reference for adding SEO to any web project.

---

## Table of Contents

0. [Fallback Strategy (Title / Description / Image)](#0-fallback-strategy-title--description--image)
1. [Core Meta Tags](#1-core-meta-tags)
2. [Open Graph (Social Sharing)](#2-open-graph-social-sharing)
3. [Twitter Card](#3-twitter-card)
4. [Structured Data (JSON-LD)](#4-structured-data-json-ld)
5. [Sitemap](#5-sitemap)
6. [Robots.txt](#6-robotstxt)
7. [Canonical URLs](#7-canonical-urls)
8. [Performance (Core Web Vitals)](#8-performance-core-web-vitals)
9. [Framework-Specific Setup](#9-framework-specific-setup)
10. [Checklist](#10-checklist)
11. [hreflang — Multi-language & Region](#11-hreflang--multi-language--region)
12. [Favicon & App Icons](#12-favicon--app-icons)
13. [Pagination SEO](#13-pagination-seo)
14. [404 & Redirect Handling](#14-404--redirect-handling)
15. [More Structured Data Schemas](#15-more-structured-data-schemas)
16. [Advanced Robots Controls](#16-advanced-robots-controls)
17. [Prerender / SSG vs SSR](#17-prerender--ssg-vs-ssr)
18. [Image SEO](#18-image-seo)
19. [Monitoring & Maintenance](#19-monitoring--maintenance)

---

## 0. Fallback Strategy (Title / Description / Image)

Always define site-wide defaults so every page has valid SEO even if per-page values are missing.

### The fallback chain (apply in this order)

```
page-specific value  →  section/category default  →  site-wide default  →  hard-coded fallback
```

### Vanilla HTML / any template engine

```html
<!--
  Define defaults once (e.g. in a layout file or .env):
    DEFAULT_TITLE       = "Site Name"
    DEFAULT_DESCRIPTION = "One sentence about what this site does."
    DEFAULT_OG_IMAGE    = "https://example.com/images/og-default.jpg"
-->

<title>{{ page.title || site.defaultTitle }}</title>

<meta name="description"
  content="{{ page.description || page.excerpt || site.defaultDescription }}" />

<meta property="og:title"
  content="{{ page.ogTitle || page.title || site.defaultTitle }}" />

<meta property="og:description"
  content="{{ page.ogDescription || page.description || page.excerpt || site.defaultDescription }}" />

<meta property="og:image"
  content="{{ page.ogImage || page.coverImage || page.thumbnail || site.defaultOgImage }}" />
```

### Next.js (App Router)

```ts
// lib/seo-defaults.ts
export const seoDefaults = {
  title:       'Site Name',
  description: 'One sentence about what this site does.',
  image:       'https://example.com/images/og-default.jpg',
}
```

```ts
// app/blog/[slug]/page.tsx
import { seoDefaults } from '@/lib/seo-defaults'

export async function generateMetadata({ params }): Promise<Metadata> {
  const post = await getPost(params.slug)

  const title       = post?.title       ?? seoDefaults.title
  const description = post?.excerpt     ?? seoDefaults.description
  const image       = post?.coverImage  ?? seoDefaults.image

  return {
    title,
    description,
    openGraph: { title, description, images: [{ url: image }] },
    twitter:   { title, description, images: [image] },
  }
}
```

### React (react-helmet-async)

```tsx
// components/SEO.tsx
const DEFAULTS = {
  title:       'Site Name',
  description: 'One sentence about what this site does.',
  image:       'https://example.com/images/og-default.jpg',
}

interface SEOProps {
  title?:       string
  description?: string
  image?:       string
  url?:         string
}

export function SEO({ title, description, image, url }: SEOProps) {
  const t = title       || DEFAULTS.title
  const d = description || DEFAULTS.description
  const i = image       || DEFAULTS.image

  return (
    <Helmet>
      <title>{t}</title>
      <meta name="description"        content={d} />
      <meta property="og:title"       content={t} />
      <meta property="og:description" content={d} />
      <meta property="og:image"       content={i} />
      {url && <link rel="canonical"   href={url} />}
      <meta name="twitter:card"        content="summary_large_image" />
      <meta name="twitter:title"       content={t} />
      <meta name="twitter:description" content={d} />
      <meta name="twitter:image"       content={i} />
    </Helmet>
  )
}

// Usage — only pass what you have; rest falls back automatically
<SEO title="My Post" image={post.cover} />
```

### Nuxt 3

```ts
// composables/useSeoFallback.ts
const DEFAULTS = {
  title:       'Site Name',
  description: 'One sentence about what this site does.',
  image:       'https://example.com/images/og-default.jpg',
}

export function useSeoFallback(opts: { title?: string; description?: string; image?: string; url?: string }) {
  useSeoMeta({
    title:           opts.title       || DEFAULTS.title,
    description:     opts.description || DEFAULTS.description,
    ogTitle:         opts.title       || DEFAULTS.title,
    ogDescription:   opts.description || DEFAULTS.description,
    ogImage:         opts.image       || DEFAULTS.image,
    twitterCard:     'summary_large_image',
    twitterTitle:    opts.title       || DEFAULTS.title,
    twitterDescription: opts.description || DEFAULTS.description,
    twitterImage:    opts.image       || DEFAULTS.image,
  })
  if (opts.url) useHead({ link: [{ rel: 'canonical', href: opts.url }] })
}
```

```vue
<!-- Per-page usage -->
<script setup>
useSeoFallback({ title: page.title, image: page.hero })
</script>
```

### Astro

```astro
---
// src/components/SEO.astro
const DEFAULTS = {
  title:       'Site Name',
  description: 'One sentence about what this site does.',
  image:       '/images/og-default.jpg',
}

const {
  title       = DEFAULTS.title,
  description = DEFAULTS.description,
  image       = DEFAULTS.image,
  url         = Astro.url.href,
} = Astro.props
---
<title>{title}</title>
<meta name="description"         content={description} />
<link rel="canonical"            href={url} />
<meta property="og:title"        content={title} />
<meta property="og:description"  content={description} />
<meta property="og:image"        content={new URL(image, Astro.site)} />
<meta name="twitter:card"         content="summary_large_image" />
<meta name="twitter:title"        content={title} />
<meta name="twitter:description"  content={description} />
<meta name="twitter:image"        content={new URL(image, Astro.site)} />
```

### Laravel (Blade)

```php
{{-- config/seo.php --}}
return [
    'title'       => 'Site Name',
    'description' => 'One sentence about what this site does.',
    'image'       => 'https://example.com/images/og-default.jpg',
];
```

```blade
{{-- layouts/app.blade.php --}}
@php
  $seoTitle       = $seoTitle       ?? config('seo.title');
  $seoDescription = $seoDescription ?? config('seo.description');
  $seoImage       = $seoImage       ?? config('seo.image');
@endphp

<title>{{ $seoTitle }}</title>
<meta name="description"        content="{{ $seoDescription }}">
<meta property="og:title"       content="{{ $seoTitle }}">
<meta property="og:description" content="{{ $seoDescription }}">
<meta property="og:image"       content="{{ $seoImage }}">
```

```php
{{-- Per page — only set what you have --}}
@php $seoTitle = 'Page Title'; $seoImage = $post->cover_url; @endphp
@extends('layouts.app')
```

---

## 1. Core Meta Tags

Place inside `<head>` on every page. Replace placeholders with per-page values.

```html
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />

  <!-- Primary SEO -->
  <title>Page Title – Site Name</title>
  <meta name="description" content="150–160 character description of this page." />
  <meta name="keywords" content="keyword1, keyword2, keyword3" />
  <meta name="author" content="Your Name or Company" />
  <meta name="robots" content="index, follow" />
  <link rel="canonical" href="https://example.com/current-page/" />
</head>
```

**Rules:**
- `<title>`: 50–60 characters. Unique per page. Put the primary keyword first.
- `description`: 150–160 characters. A human sentence, not a keyword list.
- `canonical`: always the full absolute URL of the preferred version of the page.

---

## 2. Open Graph (Social Sharing)

Controls how the page looks when shared on Facebook, LinkedIn, WhatsApp, etc.

```html
<meta property="og:type"        content="website" />
<meta property="og:url"         content="https://example.com/current-page/" />
<meta property="og:title"       content="Page Title – Site Name" />
<meta property="og:description" content="150–160 character description." />
<meta property="og:image"       content="https://example.com/images/og-cover.jpg" />
<meta property="og:image:width" content="1200" />
<meta property="og:image:height"content="630" />
<meta property="og:site_name"   content="Site Name" />
<meta property="og:locale"      content="en_US" />
```

**Image requirements:** 1200 × 630 px, ≤ 1 MB, JPG or PNG.  
For articles add: `<meta property="article:published_time" content="2024-01-15T08:00:00Z" />`

---

## 3. Twitter Card

```html
<meta name="twitter:card"        content="summary_large_image" />
<meta name="twitter:site"        content="@YourTwitterHandle" />
<meta name="twitter:creator"     content="@AuthorHandle" />
<meta name="twitter:title"       content="Page Title – Site Name" />
<meta name="twitter:description" content="150–160 character description." />
<meta name="twitter:image"       content="https://example.com/images/og-cover.jpg" />
```

Use `summary` instead of `summary_large_image` for small square thumbnails.

---

## 4. Structured Data (JSON-LD)

Add inside `<script type="application/ld+json">` in `<head>` or before `</body>`.

### Website / Organization

```json
{
  "@context": "https://schema.org",
  "@type": "Organization",
  "name": "Site Name",
  "url": "https://example.com",
  "logo": "https://example.com/logo.png",
  "sameAs": [
    "https://twitter.com/handle",
    "https://linkedin.com/company/handle"
  ]
}
```

### Article / Blog Post

```json
{
  "@context": "https://schema.org",
  "@type": "Article",
  "headline": "Article Title",
  "description": "Short description.",
  "image": "https://example.com/images/article.jpg",
  "author": { "@type": "Person", "name": "Author Name" },
  "publisher": {
    "@type": "Organization",
    "name": "Site Name",
    "logo": { "@type": "ImageObject", "url": "https://example.com/logo.png" }
  },
  "datePublished": "2024-01-15",
  "dateModified": "2024-06-01"
}
```

### Breadcrumbs

```json
{
  "@context": "https://schema.org",
  "@type": "BreadcrumbList",
  "itemListElement": [
    { "@type": "ListItem", "position": 1, "name": "Home", "item": "https://example.com/" },
    { "@type": "ListItem", "position": 2, "name": "Blog", "item": "https://example.com/blog/" },
    { "@type": "ListItem", "position": 3, "name": "Article Title" }
  ]
}
```

Validate at: https://search.google.com/test/rich-results

---

## 5. Sitemap

### Static (`/public/sitemap.xml`)

```xml
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <url>
    <loc>https://example.com/</loc>
    <lastmod>2024-01-15</lastmod>
    <changefreq>weekly</changefreq>
    <priority>1.0</priority>
  </url>
  <url>
    <loc>https://example.com/about/</loc>
    <lastmod>2024-01-15</lastmod>
    <changefreq>monthly</changefreq>
    <priority>0.8</priority>
  </url>
</urlset>
```

### Dynamic (Next.js `app/sitemap.ts`)

```ts
import { MetadataRoute } from 'next'

export default function sitemap(): MetadataRoute.Sitemap {
  return [
    { url: 'https://example.com/', lastModified: new Date(), changeFrequency: 'weekly', priority: 1 },
    { url: 'https://example.com/about/', lastModified: new Date(), changeFrequency: 'monthly', priority: 0.8 },
  ]
}
```

Submit at: Google Search Console → Sitemaps

---

## 6. Robots.txt

Create at `/public/robots.txt` (or project root for static sites).

```
User-agent: *
Allow: /

# Block private or low-value paths
Disallow: /admin/
Disallow: /api/
Disallow: /_next/
Disallow: /private/

Sitemap: https://example.com/sitemap.xml
```

---

## 7. Canonical URLs

Use canonicals to prevent duplicate-content penalties.

```html
<!-- Self-referencing canonical (every page) -->
<link rel="canonical" href="https://example.com/exact-page-url/" />

<!-- Pagination: point all pages to the first -->
<link rel="canonical" href="https://example.com/blog/" />
```

Also set `X-Robots-Tag: noindex` in HTTP headers for pages you never want indexed (e.g. staging).

---

## 8. Performance (Core Web Vitals)

Google uses LCP, CLS, and INP as ranking signals.

```html
<!-- Preconnect to critical third-party origins -->
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />

<!-- Preload the LCP image (above-the-fold hero) -->
<link rel="preload" as="image" href="/images/hero.webp" fetchpriority="high" />

<!-- Lazy-load below-fold images -->
<img src="/images/photo.webp" alt="Description" loading="lazy" width="800" height="600" />

<!-- Always set width + height on every <img> to prevent CLS -->
```

**Quick wins:**
- Serve images as WebP or AVIF.
- Use `font-display: swap` for web fonts.
- Minify and compress HTML/CSS/JS.
- Enable HTTP/2 and gzip/Brotli on the server.

Measure at: https://pagespeed.web.dev

---

## 9. Framework-Specific Setup

### Next.js (App Router)

```ts
// app/layout.tsx  –  site-wide defaults
import { Metadata } from 'next'

export const metadata: Metadata = {
  metadataBase: new URL('https://example.com'),
  title: { default: 'Site Name', template: '%s – Site Name' },
  description: 'Default site description.',
  openGraph: { type: 'website', siteName: 'Site Name' },
  twitter: { card: 'summary_large_image', site: '@handle' },
  robots: { index: true, follow: true },
}
```

```ts
// app/blog/[slug]/page.tsx  –  per-page override
export async function generateMetadata({ params }): Promise<Metadata> {
  const post = await getPost(params.slug)
  return {
    title: post.title,
    description: post.excerpt,
    openGraph: { title: post.title, description: post.excerpt, images: [post.coverImage] },
  }
}
```

### React (react-helmet or react-helmet-async)

```tsx
import { Helmet } from 'react-helmet-async'

function PageSEO({ title, description, image, url }) {
  return (
    <Helmet>
      <title>{title}</title>
      <meta name="description" content={description} />
      <link rel="canonical" href={url} />
      <meta property="og:title" content={title} />
      <meta property="og:description" content={description} />
      <meta property="og:image" content={image} />
      <meta property="og:url" content={url} />
      <meta name="twitter:card" content="summary_large_image" />
    </Helmet>
  )
}
```

### Nuxt 3

```ts
// nuxt.config.ts
export default defineNuxtConfig({
  app: {
    head: {
      titleTemplate: '%s – Site Name',
      meta: [
        { name: 'description', content: 'Default description.' },
      ],
    },
  },
})
```

```vue
<!-- Per-page -->
<script setup>
useSeoMeta({
  title: 'Page Title',
  description: 'Page description.',
  ogImage: 'https://example.com/og.jpg',
  twitterCard: 'summary_large_image',
})
</script>
```

### Astro

```astro
---
// src/components/SEO.astro
const { title, description, image, url } = Astro.props
---
<title>{title}</title>
<meta name="description" content={description} />
<link rel="canonical" href={url} />
<meta property="og:title" content={title} />
<meta property="og:description" content={description} />
<meta property="og:image" content={image} />
<meta name="twitter:card" content="summary_large_image" />
```

### Laravel (Blade)

```php
{{-- resources/views/layouts/app.blade.php --}}
<title>@yield('title', 'Site Name') – Site Name</title>
<meta name="description" content="@yield('description', 'Default description.')">
<link rel="canonical" href="{{ url()->current() }}">
<meta property="og:title" content="@yield('title', 'Site Name')">
<meta property="og:description" content="@yield('description')">
<meta property="og:url" content="{{ url()->current() }}">
```

```php
{{-- Per page --}}
@section('title', 'Page Title')
@section('description', 'Page description.')
```

---

## 10. Checklist

Copy this into your PR template or task tracker.

### Every Page
- [ ] Unique `<title>` (50–60 chars, keyword first)
- [ ] Unique `<meta name="description">` (150–160 chars)
- [ ] `<link rel="canonical">` pointing to canonical URL
- [ ] `<meta name="robots" content="index, follow">` (or `noindex` where needed)
- [ ] Open Graph tags (`og:title`, `og:description`, `og:image`, `og:url`)
- [ ] Twitter Card tags
- [ ] All `<img>` have `alt`, `width`, and `height`

### Site-Wide (once)
- [ ] `robots.txt` at root, with `Sitemap:` directive
- [ ] `sitemap.xml` generated and submitted to Google Search Console
- [ ] JSON-LD for Organization / Website on homepage
- [ ] Structured data on article/product/breadcrumb pages
- [ ] FAQ / Product / LocalBusiness / Video schema where applicable
- [ ] `preconnect` for critical third-party origins
- [ ] Hero/LCP image preloaded with `fetchpriority="high"`
- [ ] PageSpeed score ≥ 90 on mobile
- [ ] Site verified in Google Search Console
- [ ] Bing Webmaster Tools set up
- [ ] `hreflang` tags if multi-language (with `x-default`)
- [ ] Favicon set: `favicon.ico`, `favicon.svg`, `apple-touch-icon.png`, `site.webmanifest`
- [ ] `theme-color` meta for light + dark
- [ ] Pagination uses `rel="prev"` / `rel="next"` (no noindex on page 2+)
- [ ] All redirects are 301 (not chains), removed pages return 404
- [ ] No soft 404s (pages returning 200 with "not found" content)
- [ ] SEO-critical pages are SSG or SSR (not CSR-only)
- [ ] All images: descriptive filename, alt text, `width`/`height`, WebP/AVIF format
- [ ] `X-Robots-Tag: noindex` on staging / API / dashboard routes
- [ ] Post-deploy regression check script run (see §19)

### Tools
| Purpose | Tool |
|---|---|
| Validate meta tags | https://metatags.io |
| Test structured data | https://search.google.com/test/rich-results |
| Check performance | https://pagespeed.web.dev |
| Preview social cards | https://www.opengraph.xyz |
| Search Console | https://search.google.com/search-console |
| Sitemap submit | Google Search Console → Sitemaps |

---

## 11. hreflang — Multi-language & Region

Tell Google which language/region each page targets so the right version shows for each audience.

```html
<!-- Add inside <head> on EVERY language version of the page -->
<link rel="alternate" hreflang="en"    href="https://example.com/about/" />
<link rel="alternate" hreflang="ar"    href="https://example.com/ar/about/" />
<link rel="alternate" hreflang="fr"    href="https://example.com/fr/about/" />
<!-- x-default = fallback when no language matches (usually the English version) -->
<link rel="alternate" hreflang="x-default" href="https://example.com/about/" />
```

**Rules:**
- Every language version must link to ALL other versions (including itself).
- `hreflang` values follow BCP 47: `en`, `en-US`, `en-GB`, `ar`, `fr-CA`, etc.
- Always include `x-default`.
- Can also be declared in the sitemap or via HTTP header instead of `<head>`.

### In the sitemap (alternative to `<head>`)

```xml
<url>
  <loc>https://example.com/about/</loc>
  <xhtml:link rel="alternate" hreflang="en"        href="https://example.com/about/" />
  <xhtml:link rel="alternate" hreflang="ar"        href="https://example.com/ar/about/" />
  <xhtml:link rel="alternate" hreflang="x-default" href="https://example.com/about/" />
</url>
```

### Next.js App Router

```ts
export async function generateMetadata({ params }): Promise<Metadata> {
  return {
    alternates: {
      canonical: `https://example.com/${params.locale}/about/`,
      languages: {
        'en':        'https://example.com/en/about/',
        'ar':        'https://example.com/ar/about/',
        'x-default': 'https://example.com/en/about/',
      },
    },
  }
}
```

---

## 12. Favicon & App Icons

A complete set covers every browser, OS, and PWA context.

```html
<head>
  <!-- Standard favicon -->
  <link rel="icon" href="/favicon.ico" sizes="any" />
  <link rel="icon" href="/favicon.svg" type="image/svg+xml" />

  <!-- Apple devices -->
  <link rel="apple-touch-icon" href="/apple-touch-icon.png" />
  <!-- apple-touch-icon.png: 180×180 px -->

  <!-- PWA manifest -->
  <link rel="manifest" href="/site.webmanifest" />

  <!-- Browser/OS theme color -->
  <meta name="theme-color" content="#ffffff" media="(prefers-color-scheme: light)" />
  <meta name="theme-color" content="#0f172a" media="(prefers-color-scheme: dark)" />
</head>
```

### `/public/site.webmanifest`

```json
{
  "name": "Site Name",
  "short_name": "Site",
  "description": "One sentence about this site.",
  "start_url": "/",
  "display": "standalone",
  "background_color": "#ffffff",
  "theme_color": "#ffffff",
  "icons": [
    { "src": "/icons/icon-192.png", "sizes": "192x192", "type": "image/png" },
    { "src": "/icons/icon-512.png", "sizes": "512x512", "type": "image/png" },
    { "src": "/icons/icon-512-maskable.png", "sizes": "512x512", "type": "image/png", "purpose": "maskable" }
  ]
}
```

**Minimum files to generate:**
| File | Size |
|---|---|
| `favicon.ico` | 32×32 (multi-size: 16, 32, 48) |
| `favicon.svg` | vector, works at any size |
| `apple-touch-icon.png` | 180×180 |
| `icon-192.png` | 192×192 |
| `icon-512.png` | 512×512 |
| `icon-512-maskable.png` | 512×512, safe zone 80% |

Generator: https://realfavicongenerator.net

---

## 13. Pagination SEO

For paginated lists (/blog/, /blog/page/2/, etc.).

```html
<!-- On page 1 -->
<link rel="next" href="https://example.com/blog/page/2/" />

<!-- On page 2 -->
<link rel="prev" href="https://example.com/blog/" />
<link rel="next" href="https://example.com/blog/page/3/" />

<!-- On last page -->
<link rel="prev" href="https://example.com/blog/page/4/" />

<!-- canonical on every paginated page points to ITSELF, not page 1 -->
<link rel="canonical" href="https://example.com/blog/page/2/" />
```

**Do not** put `noindex` on page 2+. Google indexes all pages individually.  
**Do not** set canonical on page 2+ pointing to page 1 — that tells Google to ignore the other pages.

### Next.js dynamic pagination

```ts
export async function generateMetadata({ searchParams }): Promise<Metadata> {
  const page = Number(searchParams.page) || 1
  const base = 'https://example.com/blog'

  return {
    alternates: {
      canonical: page === 1 ? base : `${base}/page/${page}`,
    },
  }
}
```

---

## 14. 404 & Redirect Handling

### Correct HTTP status codes

| Situation | Status |
|---|---|
| Page not found | `404 Not Found` |
| Page moved permanently | `301 Moved Permanently` |
| Page moved temporarily | `302 Found` |
| URL changed, keep SEO value | `301` |
| Canonical consolidation | `301` |

A "soft 404" is when a page returns `200 OK` but shows "not found" content — Google penalises these.

### Next.js

```ts
// app/not-found.tsx — returns 404 automatically
export default function NotFound() {
  return <h1>Page not found</h1>
}

// Redirect in next.config.ts
const nextConfig = {
  async redirects() {
    return [
      { source: '/old-path', destination: '/new-path', permanent: true }, // 301
      { source: '/temp',     destination: '/new-temp', permanent: false }, // 302
    ]
  },
}
```

### Laravel

```php
// routes/web.php — permanent redirect
Route::permanentRedirect('/old-path', '/new-path'); // 301

// Abort with 404
abort(404);

// Custom 404 page: resources/views/errors/404.blade.php
```

### Nginx

```nginx
# 301 redirect
rewrite ^/old-path$ /new-path permanent;

# Custom 404
error_page 404 /404.html;
```

### `_redirects` file (Netlify / Cloudflare Pages)

```
/old-path   /new-path   301
/temp-page  /new-temp   302
```

**Avoid redirect chains** — A → B → C. Consolidate to A → C directly.

---

## 15. More Structured Data Schemas

### FAQ

Renders expandable questions directly in Google search results.

```json
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "mainEntity": [
    {
      "@type": "Question",
      "name": "What is your return policy?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "We accept returns within 30 days of purchase."
      }
    },
    {
      "@type": "Question",
      "name": "Do you ship internationally?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Yes, we ship to over 50 countries."
      }
    }
  ]
}
```

### Product

```json
{
  "@context": "https://schema.org",
  "@type": "Product",
  "name": "Product Name",
  "description": "Short product description.",
  "image": "https://example.com/images/product.jpg",
  "brand": { "@type": "Brand", "name": "Brand Name" },
  "sku": "SKU-001",
  "offers": {
    "@type": "Offer",
    "url": "https://example.com/products/product-name",
    "priceCurrency": "USD",
    "price": "29.99",
    "availability": "https://schema.org/InStock",
    "itemCondition": "https://schema.org/NewCondition"
  },
  "aggregateRating": {
    "@type": "AggregateRating",
    "ratingValue": "4.5",
    "reviewCount": "128"
  }
}
```

### LocalBusiness

```json
{
  "@context": "https://schema.org",
  "@type": "LocalBusiness",
  "name": "Business Name",
  "image": "https://example.com/images/storefront.jpg",
  "url": "https://example.com",
  "telephone": "+1-555-123-4567",
  "email": "hello@example.com",
  "address": {
    "@type": "PostalAddress",
    "streetAddress": "123 Main St",
    "addressLocality": "City",
    "addressRegion": "State",
    "postalCode": "10001",
    "addressCountry": "US"
  },
  "geo": { "@type": "GeoCoordinates", "latitude": 40.7128, "longitude": -74.0060 },
  "openingHoursSpecification": [
    {
      "@type": "OpeningHoursSpecification",
      "dayOfWeek": ["Monday","Tuesday","Wednesday","Thursday","Friday"],
      "opens": "09:00",
      "closes": "18:00"
    }
  ]
}
```

### Video

```json
{
  "@context": "https://schema.org",
  "@type": "VideoObject",
  "name": "Video Title",
  "description": "Short description of the video.",
  "thumbnailUrl": "https://example.com/images/video-thumb.jpg",
  "uploadDate": "2024-01-15T08:00:00Z",
  "duration": "PT5M30S",
  "contentUrl": "https://example.com/videos/my-video.mp4",
  "embedUrl": "https://www.youtube.com/embed/VIDEO_ID",
  "publisher": {
    "@type": "Organization",
    "name": "Site Name",
    "logo": { "@type": "ImageObject", "url": "https://example.com/logo.png" }
  }
}
```

`duration` uses ISO 8601: `PT5M30S` = 5 minutes 30 seconds.

---

## 16. Advanced Robots Controls

### Per-page `<meta name="robots">` options

```html
<!-- Most common -->
<meta name="robots" content="index, follow" />       <!-- default -->
<meta name="robots" content="noindex, follow" />     <!-- don't index, but crawl links -->
<meta name="robots" content="index, nofollow" />     <!-- index, but don't follow links -->
<meta name="robots" content="noindex, nofollow" />   <!-- block completely -->

<!-- Fine-grained controls -->
<meta name="robots" content="noarchive" />           <!-- don't show cached version -->
<meta name="robots" content="nosnippet" />           <!-- no description snippet -->
<meta name="robots" content="max-snippet:150" />     <!-- limit snippet to 150 chars -->
<meta name="robots" content="max-image-preview:large" /> <!-- allow large image previews -->
<meta name="robots" content="max-video-preview:30" /> <!-- limit video preview to 30s -->
```

Combine values: `content="noindex, noarchive, max-snippet:0"`

### `X-Robots-Tag` HTTP header

Use for non-HTML files (PDFs, images) or to noindex entire path groups at the server level.

```nginx
# Nginx — noindex all staging subpaths
location /staging/ {
  add_header X-Robots-Tag "noindex, nofollow";
}
```

```php
// Laravel middleware — noindex non-production
if (app()->environment('staging')) {
    header('X-Robots-Tag: noindex, nofollow');
}
```

```ts
// Next.js — noindex API routes and private paths
export function middleware(request: NextRequest) {
  const response = NextResponse.next()
  if (request.nextUrl.pathname.startsWith('/api/') ||
      request.nextUrl.pathname.startsWith('/dashboard/')) {
    response.headers.set('X-Robots-Tag', 'noindex, nofollow')
  }
  return response
}
```

---

## 17. Prerender / SSG vs SSR

Google can crawl JavaScript but it is delayed and unreliable. Server-rendered HTML is always safer.

| Rendering | SEO | When to use |
|---|---|---|
| **SSG** (static) | Best — HTML ready instantly | Marketing pages, blogs, docs |
| **SSR** (server-side) | Excellent — HTML on each request | Dynamic content, personalised pages |
| **ISR** (incremental static) | Excellent | Large sites that update periodically |
| **CSR** (client-side only) | Risky — content may not be crawled | Dashboards, auth-only pages |

### Signs your CSR page is hurting SEO

- `curl https://example.com/page` returns an almost-empty `<body>`
- Google Search Console → URL Inspection shows no content
- PageSpeed shows blank screenshot

### Fix: move SEO-critical content server-side

```ts
// Next.js — fetch data at build or request time, never in useEffect
// BAD for SEO
useEffect(() => { fetch('/api/posts').then(...) }, [])

// GOOD for SEO
export async function generateStaticParams() { ... }  // SSG
// or
export const dynamic = 'force-dynamic'                // SSR
```

For legacy React SPAs with no SSR option, use **dynamic rendering** (prerender.io, Rendertron) as a stopgap.

---

## 18. Image SEO

Images appear in Google Images search and contribute to page ranking.

### Markup rules

```html
<!-- Always: descriptive alt, explicit dimensions -->
<img
  src="/images/red-running-shoes.webp"
  alt="Red lightweight running shoes on a white background"
  width="800"
  height="600"
  loading="lazy"
/>

<!-- Above-the-fold / LCP image: eager + high priority -->
<img
  src="/images/hero.webp"
  alt="Team working in a modern open office"
  width="1200"
  height="630"
  loading="eager"
  fetchpriority="high"
/>

<!-- Responsive images -->
<img
  src="/images/photo-800.webp"
  srcset="/images/photo-400.webp 400w,
          /images/photo-800.webp 800w,
          /images/photo-1200.webp 1200w"
  sizes="(max-width: 600px) 400px, (max-width: 900px) 800px, 1200px"
  alt="Description"
  width="1200"
  height="800"
/>
```

### File naming

```
# Bad
IMG_4892.jpg
image1.png
photo-final-v2.jpg

# Good
red-running-shoes-side-view.webp
team-photo-office-2024.webp
homepage-hero-product-launch.webp
```

Use hyphens, not underscores. Include the primary keyword naturally.

### Formats

| Format | Use case |
|---|---|
| **WebP** | Photos, general use — 25–35% smaller than JPEG |
| **AVIF** | Best compression, modern browsers only |
| **SVG** | Icons, logos, illustrations |
| **PNG** | Transparency needed, not available as SVG |

```html
<!-- Serve AVIF with WebP fallback -->
<picture>
  <source srcset="/images/hero.avif" type="image/avif" />
  <source srcset="/images/hero.webp" type="image/webp" />
  <img src="/images/hero.jpg" alt="Description" width="1200" height="630" />
</picture>
```

### Alt text rules

- Describe what the image shows, not what you want it to rank for.
- 8–15 words is usually enough.
- Leave `alt=""` for purely decorative images (icons beside text, dividers).
- Never stuff keywords: `alt="shoes buy shoes red shoes online shoes"` hurts ranking.

---

## 19. Monitoring & Maintenance

### Google Search Console setup

1. Go to https://search.google.com/search-console
2. Add property → choose **URL prefix** (simpler) or Domain
3. Verify ownership — easiest method: add the HTML meta tag to `<head>`:
   ```html
   <meta name="google-site-verification" content="YOUR_CODE_HERE" />
   ```
4. Submit sitemap: **Sitemaps** → enter `sitemap.xml` → Submit
5. Check weekly: **Coverage** (index errors), **Core Web Vitals**, **Performance** (clicks/impressions)

### Bing Webmaster Tools

1. Go to https://www.bing.com/webmasters
2. Add site → import from Google Search Console (one click) or verify manually
3. Submit sitemap

### After every deployment — quick SEO regression check

```bash
# 1. Confirm pages are indexable (should NOT contain noindex)
curl -s https://example.com/ | grep -i "noindex"

# 2. Check canonical is correct
curl -s https://example.com/page/ | grep -i "canonical"

# 3. Check status codes
curl -o /dev/null -s -w "%{http_code}" https://example.com/page/
# Expected: 200 for live pages, 301 for redirects, 404 for removed pages

# 4. Validate sitemap is reachable
curl -o /dev/null -s -w "%{http_code}" https://example.com/sitemap.xml
# Expected: 200
```

### What to monitor

| Signal | Where to check | Frequency |
|---|---|---|
| Index coverage errors | Search Console → Coverage | Weekly |
| Core Web Vitals (LCP, CLS, INP) | Search Console → Core Web Vitals | Weekly |
| Crawl errors | Search Console → Settings → Crawl stats | Weekly |
| Manual actions / penalties | Search Console → Manual actions | Monthly |
| Organic clicks & impressions | Search Console → Performance | Weekly |
| Page speed regression | https://pagespeed.web.dev | After each deploy |
| Broken links | https://www.screamingfrog.co.uk/seo-spider/ | Monthly |
| Structured data errors | https://search.google.com/test/rich-results | After schema changes |

### Tools reference

| Tool | Purpose |
|---|---|
| https://search.google.com/search-console | Index status, performance, errors |
| https://www.bing.com/webmasters | Bing index and crawl stats |
| https://pagespeed.web.dev | Core Web Vitals score |
| https://metatags.io | Preview meta tags |
| https://www.opengraph.xyz | Preview social cards |
| https://search.google.com/test/rich-results | Validate structured data |
| https://realfavicongenerator.net | Generate all favicon sizes |
| https://www.screamingfrog.co.uk/seo-spider/ | Crawl site for broken links, missing tags |
| https://ahrefs.com/backlink-checker | Free backlink overview |
