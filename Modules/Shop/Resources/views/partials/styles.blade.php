<style>
:root{--ink:#1c1917;--muted:#57534e;--line:#e7e5e4;--paper:#fff;--tint:#faf7f2;--accent:#a63d1c;--accent-dark:#7f2d13;--error:#b42318;--ok:#166534}
*{box-sizing:border-box}
html{-webkit-text-size-adjust:100%}
body{margin:0;background:var(--paper);color:var(--ink);font:400 16px/1.7 "IBM Plex Sans Arabic",Tahoma,sans-serif;overflow-x:clip}
img{display:block;max-width:100%;height:auto}
a{color:inherit;text-decoration:none}
a:hover{color:var(--accent)}
:focus-visible{outline:2px solid var(--accent);outline-offset:2px}
h1,h2,h3,p{margin:0}
.wrap,.page{width:100%;max-width:1200px;margin-inline:auto;padding-inline:16px}
.page{padding-block:24px 56px;min-height:60vh}
.announce{background:var(--ink);color:#fff;text-align:center;font-size:14px;padding:8px 16px}
.head{position:sticky;top:0;z-index:20;background:var(--paper);border-bottom:1px solid var(--line)}
.head-row{display:flex;align-items:center;gap:8px;min-height:64px}
.logo{display:inline-flex;align-items:center;min-height:44px;font-size:20px;font-weight:700;min-width:0;overflow-wrap:anywhere}
.nav a,.crumbs a{display:inline-flex;align-items:center;min-height:44px}
.nav{display:none;flex-wrap:nowrap;align-items:center;gap:20px;font-weight:600;font-size:15px;margin-inline-start:24px;flex:1;min-width:0;white-space:nowrap}
.more{position:relative}
.more summary{display:inline-flex;align-items:center;gap:8px;min-height:44px;cursor:pointer;list-style:none}
.more summary::-webkit-details-marker{display:none}
.more summary::after{content:"";width:7px;height:7px;border:solid currentColor;border-width:0 2px 2px 0;transform:translateY(-2px) rotate(45deg)}
.more[open] summary::after{transform:translateY(2px) rotate(225deg)}
.more-panel{position:absolute;top:100%;inset-inline-start:0;min-width:220px;padding:8px 16px;background:var(--paper);border:1px solid var(--line)}
.more-panel a{display:flex;align-items:center;min-height:44px}
.head-icons{display:flex;margin-inline-start:auto}
.icon-link{position:relative;display:flex;align-items:center;justify-content:center;width:44px;height:44px;cursor:pointer}
.count{position:absolute;top:2px;inset-inline-end:0;min-width:18px;height:18px;padding-inline:4px;background:var(--accent);color:#fff;font-size:12px;line-height:18px;text-align:center;border-radius:9px}
.menu summary{list-style:none}
.menu summary::-webkit-details-marker{display:none}
.menu-panel{position:absolute;inset-inline:0;top:100%;background:var(--paper);border-bottom:1px solid var(--line);padding:8px 16px 16px}
.menu-panel a{display:block;padding:10px 0;font-weight:600;border-bottom:1px solid var(--line)}
.crumbs{display:flex;flex-wrap:wrap;align-items:center;gap:0 8px;font-size:14px;color:var(--muted);margin:0 0 8px;padding:0;list-style:none}
.crumbs li+li::before{content:"/";margin-inline-end:8px}
.title{font-size:26px;line-height:1.35;font-weight:700;overflow-wrap:anywhere}
.section-title{font-size:21px;font-weight:700;margin:40px 0 16px}
.lead{color:var(--muted);margin-top:6px}
.hero{background:var(--tint);padding:32px 16px;text-align:center}
.cats{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}
.cat{display:block;font-weight:600;text-align:center}
.cat img{width:100%;aspect-ratio:1;object-fit:cover;background:var(--tint);margin-bottom:8px}
.cat-text{display:flex;align-items:center;justify-content:center;min-height:88px;background:var(--tint);padding:12px}
.sorts{display:flex;flex-wrap:wrap;gap:4px 18px;margin:16px 0 20px;font-size:15px;color:var(--muted)}
.sorts a{display:inline-flex;align-items:center;min-height:44px}
.sorts a[aria-current]{color:var(--ink);font-weight:700;text-decoration:underline;text-underline-offset:6px}
.grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:24px 12px}
.card{display:block;min-width:0}
.card-img{position:relative;aspect-ratio:1;background:var(--tint);overflow:hidden}
.card-img img{width:100%;height:100%;object-fit:cover}
.flag{position:absolute;top:0;inset-inline-start:0;background:var(--accent);color:#fff;font-size:13px;font-weight:600;padding:2px 8px}
.flag.out{background:var(--ink)}
.card h2,.card h3{font-size:15px;font-weight:600;line-height:1.5;margin-top:10px;overflow-wrap:anywhere}
.price{display:flex;flex-wrap:wrap;align-items:baseline;gap:2px 8px;font-weight:700}
.price del{color:var(--muted);font-weight:400;font-size:14px}
.empty{padding:48px 0;text-align:center;color:var(--muted)}
.pager{display:flex;align-items:center;justify-content:center;flex-wrap:wrap;gap:16px;margin-top:32px;font-weight:600}
.pager a{display:inline-flex;align-items:center;min-height:44px;padding-inline:16px;border:1px solid var(--ink)}
.pdp{display:grid;gap:24px;grid-template-columns:minmax(0,1fr)}
.main-img{width:100%;aspect-ratio:1;object-fit:cover;background:var(--tint)}
.thumbs{display:flex;flex-wrap:wrap;gap:8px;margin-top:8px}
.thumbs a{display:block;width:64px;height:64px;border:1px solid var(--line)}
.thumbs a[aria-current]{border-color:var(--ink)}
.thumbs img{width:100%;height:100%;object-fit:cover}
.pdp .price{font-size:24px;margin-top:8px}
.pdp .price del{font-size:17px}
.save{color:var(--accent);font-size:15px;font-weight:600}
.field{border:0;padding:0;margin:20px 0 0;min-width:0}
.field legend,.field>label{display:block;padding:0;font-weight:600;margin-bottom:6px}
.opts{display:flex;flex-wrap:wrap;gap:8px}
.opt input{position:absolute;opacity:0;width:1px;height:1px}
.opt span{display:flex;align-items:center;justify-content:center;min-width:48px;min-height:44px;padding-inline:12px;border:1px solid #a8a29e;cursor:pointer}
.opt input:checked+span{background:var(--ink);border-color:var(--ink);color:#fff}
.opt input:focus-visible+span{outline:2px solid var(--accent);outline-offset:2px}
input[type=text],input[type=tel],input[type=number],input[type=search],select,textarea{width:100%;min-height:48px;padding:10px 12px;border:1px solid #a8a29e;border-radius:0;background:#fff;color:var(--ink);font:inherit}
.qty{max-width:96px}
.err{color:var(--error);font-size:14px;margin-top:4px}
.actions{display:flex;flex-direction:column;gap:8px;margin-top:20px}
.btn{display:flex;align-items:center;justify-content:center;width:100%;min-height:50px;padding:10px 20px;border:1px solid var(--accent);background:var(--accent);color:#fff;font-family:inherit;font-size:16px;font-weight:600;line-height:1.4;cursor:pointer;transition:background-color .15s ease-out}
.btn:hover{background:var(--accent-dark);color:#fff}
.btn.alt{background:var(--paper);color:var(--ink);border-color:var(--ink)}
.btn.alt:hover{background:var(--tint);color:var(--ink)}
.link-btn{background:none;border:0;padding:0;min-height:44px;color:var(--error);font:inherit;cursor:pointer;text-decoration:underline}
.desc{margin-top:28px;border-top:1px solid var(--line);padding-top:16px;overflow-wrap:anywhere}
.line{display:grid;grid-template-columns:72px minmax(0,1fr);gap:12px;padding-block:16px;border-bottom:1px solid var(--line)}
.line img{width:72px;height:72px;object-fit:cover;background:var(--tint)}
.line-name{display:block;font-weight:600;overflow-wrap:anywhere}
.line-meta{color:var(--muted);font-size:14px}
.line-tools{display:flex;flex-wrap:wrap;align-items:center;gap:8px 16px;margin-top:8px}
.line-tools form{display:flex;align-items:center;gap:8px}
.small-btn{min-height:48px;padding-inline:12px;border:1px solid var(--ink);background:var(--paper);color:var(--ink);font:inherit;cursor:pointer}
.totals{margin-top:16px}
.totals div{display:flex;justify-content:space-between;gap:12px;padding-block:6px}
.totals .grand{font-weight:700;font-size:19px;border-top:1px solid var(--line);margin-top:6px;padding-top:12px}
.checkout{display:grid;gap:32px;grid-template-columns:minmax(0,1fr)}
.notice{background:#f0fdf4;color:var(--ok);padding:10px 12px;margin-bottom:16px}
.search-form{display:flex;gap:8px;margin-top:12px}
.search-form .btn{width:auto;flex-shrink:0}
.thanks{max-width:520px;margin-inline:auto;text-align:center;padding-block:40px}
.foot{background:var(--tint);padding-block:32px;font-size:15px}
.foot-links{display:flex;flex-wrap:wrap;gap:0 20px;margin-top:8px}
.foot-links a{display:inline-flex;align-items:center;min-height:44px}
.foot small{display:block;color:var(--muted);margin-top:12px;font-size:14px}
@media (min-width:640px){.actions{flex-direction:row}.cats{grid-template-columns:repeat(4,minmax(0,1fr))}.grid{grid-template-columns:repeat(3,minmax(0,1fr));gap:32px 20px}}
@media (min-width:1100px){.menu{display:none}.nav{display:flex}}
@media (min-width:960px){.grid{grid-template-columns:repeat(4,minmax(0,1fr))}.pdp{grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:48px}.checkout{grid-template-columns:minmax(0,3fr) minmax(0,2fr);gap:56px}.title{font-size:32px}.hero{padding-block:56px}.card h2,.card h3{font-size:16px}}
@media (prefers-reduced-motion:reduce){*{transition:none!important}}
</style>
