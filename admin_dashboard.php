<?php
require_once __DIR__ . '/config.php';
$currentUser = require_role($pdo, 'admin');
$adminNameRaw = trim((string)$currentUser['full_name']) !== '' ? (string)$currentUser['full_name'] : (string)$currentUser['username'];
$adminName = e($adminNameRaw);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard - Archives File Management System</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Noto+Sans+Sinhala:wght@400;500;600;700&family=Libre+Baskerville:wght@400;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

<style>
:root{
    --ink:#14233d; --navy:#0b2146; --navy-2:#16386a; --gold:#d4a24a; --gold-2:#f0cf8a; --gold-soft:#fbf3e1;
    --bg:#eef2f8; --card:#fff; --line:#e1e7f1; --muted:#6a7890; --blue:#2563d0; --red:#c93636;
    --shadow:0 1px 2px rgba(16,37,71,.05),0 10px 28px rgba(16,37,71,.07);
    --fs-xs:11px; --fs-sm:12px; --fs-md:13px; --fs-lg:14px; --fs-xl:17px;
    --serif:'Libre Baskerville',Georgia,'Noto Sans Sinhala',serif;
    --ink-inst:#2f6fe0; --ink-coun:#c98a1b; --ink-acct:#17946a; --stamp:#b3414f; --rule:#d9cfb8;
}
*{margin:0;padding:0;box-sizing:border-box}
html{scroll-behavior:smooth}
body{font-family:'Plus Jakarta Sans','Noto Sans Sinhala',sans-serif;font-size:var(--fs-md);line-height:1.6;color:var(--ink);min-height:100vh;-webkit-font-smoothing:antialiased;
    background-color:var(--bg);
    background-image:radial-gradient(rgba(22,56,106,.075) 1px,transparent 1.2px),radial-gradient(900px 420px at 100% 0,rgba(212,162,74,.12),transparent 60%),radial-gradient(800px 500px at 30% -10%,rgba(37,99,208,.10),transparent 60%);
    background-size:22px 22px,auto,auto}
button,input,select,textarea{font:inherit}
button{cursor:pointer}
[hidden]{display:none!important}
:focus-visible{outline:2px solid var(--blue);outline-offset:2px}
::selection{background:rgba(212,162,74,.35);color:var(--navy)}
.app{display:flex;min-height:100vh}

/* ===== SIDEBAR (with faint shelf lines) ===== */
.sidebar{width:272px;flex-shrink:0;display:flex;flex-direction:column;position:sticky;top:0;height:100vh;z-index:20;overflow-x:hidden;overflow-y:auto;color:#fff;
    background:linear-gradient(180deg,#0c2347 0%,#091b38 100%);transition:width .3s ease;scrollbar-width:none;border-right:1px solid rgba(255,255,255,.06)}
.sidebar::-webkit-scrollbar{display:none}
.sidebar::before{content:"";position:absolute;inset:0;pointer-events:none;background:radial-gradient(ellipse at 50% 0,rgba(212,162,74,.16),transparent 55%)}
.sidebar::after{content:"";position:absolute;inset:0;pointer-events:none;background:repeating-linear-gradient(0deg,transparent 0 55px,rgba(255,255,255,.028) 55px 56px)}
.sidebar>*{position:relative;z-index:1}
.brand{display:flex;flex-direction:column;align-items:center;text-align:center;gap:14px;padding:14px 10px 16px;margin-bottom:2px}
.brand::after{content:"";position:absolute;left:24px;right:24px;bottom:0;height:1px;background:linear-gradient(90deg,transparent,rgba(212,162,74,.55),transparent)}
.logo-ring{width:36px;height:36px;position:relative;display:grid;place-items:center;flex-shrink:0}
.logo-ring::before{content:"";position:absolute;inset:6px;border-radius:50%;background:radial-gradient(circle,rgba(212,162,74,.28),transparent 68%)}
.logo-box{position:relative;width:100%;height:100%;display:flex;align-items:center;justify-content:center}
.logo{width:100%;height:100%;object-fit:contain;filter:drop-shadow(0 6px 14px rgba(0,0,0,.45))}
.brand-text .sinhala{font-family:'Noto Sans Sinhala',sans-serif;font-size:14px;font-weight:700;line-height:1.7}
.brand-text .english{font-size:10.5px;line-height:1.5;color:#aebfdc;margin-top:2px;letter-spacing:.3px}
.brand-text .system-name{display:inline-flex;align-items:center;gap:6px;font-size:10px;font-weight:600;letter-spacing:.2px;white-space:nowrap;color:var(--gold-2);margin-top:12px;padding:5px 11px;border-radius:6px;border:1px solid rgba(212,162,74,.4);background:rgba(212,162,74,.08)}
.sidebar-label{padding:10px 26px 6px;color:#7389ad;font-size:11px;font-weight:700;letter-spacing:.6px}
.sidebar-menu{padding:0 14px}
.nav-link{display:flex;align-items:center;gap:14px;width:100%;min-height:36px;padding:4px 14px;border:0;border-radius:8px;background:transparent;text-align:left;text-decoration:none;color:#b7c6df;font-weight:500;position:relative;transition:background .2s,color .2s,transform .2s}
.nav-link i{width:20px;text-align:center;font-size:14px;color:#7f9bc6;transition:color .2s}
.nav-link:hover{color:#fff;background:rgba(255,255,255,.06);transform:translateX(4px)}
.nav-link:hover i{color:#fff}
.nav-link.active{color:#fff;font-weight:600;background:linear-gradient(90deg,rgba(212,162,74,.22),rgba(255,255,255,.04));box-shadow:inset 0 0 0 1px rgba(212,162,74,.18)}
.nav-link.active i{color:var(--gold)}
.nav-link.active::before{content:"";position:absolute;left:-14px;top:8px;bottom:8px;width:4px;border-radius:0 4px 4px 0;background:var(--gold);box-shadow:0 0 12px rgba(212,162,74,.7)}
.nav-link.logout-link{color:#f2b8bf}
.nav-link.logout-link i{color:#ef8a96}
.nav-link.logout-link:hover{background:rgba(239,90,110,.14);color:#fff}
.nav-badge{margin-left:auto;min-width:20px;padding:1px 7px;border-radius:20px;background:var(--gold);color:#1b1203;font-size:10px;font-weight:700;text-align:center}
.sidebar-bottom{margin-top:auto;padding:12px 38px}
.sidebar-quote{position:relative;overflow:hidden;padding:10px 10px 9px;border-radius:10px;text-align:center;background:linear-gradient(160deg,rgba(212,162,74,.18),rgba(255,255,255,.04) 55%);border:1px solid rgba(212,162,74,.35);box-shadow:0 4px 12px rgba(0,0,0,.25),inset 0 1px 0 rgba(255,255,255,.08)}
.sidebar-quote::before{content:"";position:absolute;left:18%;right:18%;top:0;height:2px;background:linear-gradient(90deg,transparent,var(--gold),transparent)}
.quote-mark{position:absolute;right:8px;top:5px;font-size:16px;color:rgba(212,162,74,.18)}
.quote-top{display:flex;align-items:center;justify-content:center;gap:6px;margin-bottom:5px;color:var(--gold-2);font-size:8px;font-weight:700;letter-spacing:1.2px}
.quote-top::before,.quote-top::after{content:"";width:10px;height:1px;background:rgba(212,162,74,.6)}
.sidebar-quote p{position:relative;font-size:10.5px;line-height:1.4;font-weight:600;color:#e8eefb}
.sidebar-quote p strong{display:block;margin-top:1px;padding-bottom:3px;margin-bottom:-3px;font-size:12px;font-weight:800;background:linear-gradient(90deg,var(--gold-2),var(--gold));-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent}
.quote-line{width:22px;height:2px;margin:7px auto 0;border-radius:3px;background:linear-gradient(90deg,var(--gold),var(--gold-2))}

/* ===== TOPBAR ===== */
.main{flex:1;min-width:0}
.topbar{min-height:66px;display:flex;justify-content:space-between;align-items:center;gap:14px;padding:10px 28px;background:rgba(255,255,255,.82);border-bottom:1px solid var(--line);position:sticky;top:0;z-index:12;backdrop-filter:blur(14px);box-shadow:0 4px 18px rgba(16,37,71,.05)}
.topbar::after{content:"";position:absolute;left:0;right:0;bottom:-1px;height:2px;background:linear-gradient(90deg,transparent,rgba(212,162,74,.55) 50%,transparent)}
.topbar-left{display:flex;align-items:center;gap:14px;min-width:0}
.menu-toggle{width:40px;height:40px;flex-shrink:0;display:grid;place-items:center;background:#fff;border:1px solid var(--line);border-radius:12px;color:var(--navy);font-size:14px;transition:.2s}
.menu-toggle:hover{color:#fff;background:var(--navy)}
.topbar-title{display:flex;align-items:center;gap:12px;min-width:0}
.title-icon{width:42px;height:42px;flex-shrink:0;display:grid;place-items:center;border-radius:13px;background:linear-gradient(135deg,var(--navy),var(--navy-2));color:var(--gold-2);font-size:17px;box-shadow:0 8px 18px rgba(13,37,71,.28),inset 0 1px 0 rgba(255,255,255,.18)}
.title-text{min-width:0;padding-left:14px;border-left:2px solid var(--gold)}
.title-text h1{font-family:var(--serif);font-size:19px;font-weight:700;line-height:1.2;color:var(--navy)}
.title-text>span{display:block;font-size:var(--fs-xs);color:var(--muted);margin-top:2px}
.profile{display:flex;align-items:center;gap:10px;position:relative;flex-shrink:0;padding:5px 8px 5px 5px;background:#fff;border:1px solid var(--line);border-radius:40px;box-shadow:0 2px 8px rgba(16,37,71,.06);transition:box-shadow .2s,border-color .2s}
.profile:hover{border-color:#cdd8ea;box-shadow:0 6px 16px rgba(16,37,71,.1)}
.profile-avatar{width:36px;height:36px;border-radius:50%;display:grid;place-items:center;color:var(--navy);background:linear-gradient(135deg,var(--gold-2),var(--gold));font-size:14px;box-shadow:0 0 0 3px rgba(212,162,74,.22)}
.profile-details strong{display:block;font-size:var(--fs-sm);font-weight:700;line-height:1.3}
.profile-details span{font-size:var(--fs-xs);color:var(--muted)}
.profile-toggle{border:0;background:transparent;color:#5e7290;padding:8px;font-size:11px}
.profile-popover{position:absolute;right:0;top:56px;width:230px;padding:16px;background:#fff;border:1px solid var(--line);border-radius:14px;box-shadow:0 14px 34px rgba(15,37,71,.16);z-index:30}
.profile-popover strong{font-size:var(--fs-md)}
.profile-popover p{color:var(--muted);font-size:var(--fs-sm);margin-top:2px}
.profile-popover a{display:block;padding:9px 0;text-decoration:none;color:#27487a;font-size:var(--fs-sm)}
.profile-popover a:first-of-type{margin-top:8px;border-top:1px solid var(--line)}

/* ===== CONTENT ===== */
.content{max-width:1560px;margin:0 auto;padding:24px 28px 30px}

/* Hero with archive-shelf watermark */
.hero{position:relative;overflow:hidden;display:grid;grid-template-columns:1fr;gap:18px;align-items:center;padding:22px 24px;margin-bottom:24px;border-radius:22px;color:#fff;
    background:linear-gradient(120deg,#0a1d3f 0%,#16386a 55%,#215596 100%);box-shadow:0 18px 40px rgba(13,37,71,.25);animation:fadeUp .55s cubic-bezier(.2,.7,.2,1) both}
.hero::before{content:"";position:absolute;inset:0;background-image:linear-gradient(rgba(255,255,255,.045) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.045) 1px,transparent 1px);background-size:36px 36px;mask-image:linear-gradient(90deg,transparent 10%,#000 90%);-webkit-mask-image:linear-gradient(90deg,transparent 10%,#000 90%)}
.hero::after{content:"";position:absolute;width:320px;height:320px;right:-110px;top:-160px;border-radius:50%;border:1px solid rgba(212,162,74,.4);box-shadow:0 0 0 40px rgba(212,162,74,.05);animation:floatGlow 9s ease-in-out infinite}
.hero>*{position:relative;z-index:1}
.hero>.hero-art{position:absolute;right:-8px;top:-10px;width:380px;max-width:42%;opacity:.14;z-index:0;pointer-events:none}
.hero-date{display:inline-flex;align-items:center;gap:8px;padding:5px 13px;border-radius:20px;background:rgba(240,207,138,.14);border:1px solid rgba(240,207,138,.35);font-size:var(--fs-xs);color:#f6e6bd;backdrop-filter:blur(6px)}
.hero h1{font-family:var(--serif);font-size:28px;font-weight:700;line-height:1.35;margin:14px 0 4px;background:linear-gradient(90deg,#fff,var(--gold-2));-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent}
.hero p{font-size:var(--fs-md);color:#bccbe3;max-width:520px}
.hero-stats{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:10px}

/* Stat cards: index-card look (dashed inner border) */
.stat-card{position:relative;overflow:hidden;padding:14px 12px 12px;border-radius:16px;background:linear-gradient(160deg,rgba(255,255,255,.14),rgba(255,255,255,.05));border:1px solid rgba(255,255,255,.15);backdrop-filter:blur(6px);transition:background .2s,transform .2s}
.stat-card::before{content:"";position:absolute;left:0;top:0;right:0;height:3px;background:linear-gradient(90deg,var(--gold),var(--gold-2),transparent)}
.stat-card::after{content:"";position:absolute;inset:6px;border:1px dashed rgba(240,207,138,.28);border-radius:11px;pointer-events:none}
.stat-card:hover{background:rgba(255,255,255,.15);transform:translateY(-3px)}
.stat-icon{position:relative;width:30px;height:30px;display:grid;place-items:center;border-radius:9px;background:linear-gradient(135deg,var(--gold-2),var(--gold));color:#1b1203;font-size:12px;margin-bottom:8px;box-shadow:0 8px 18px rgba(212,162,74,.35)}
.stat-info h2{font-family:var(--serif);font-size:22px;line-height:1.1;font-weight:700;text-shadow:0 2px 12px rgba(0,0,0,.2)}
.stat-info span{display:block;font-size:11px;font-weight:600;color:#e4ecf8;margin-top:5px;line-height:1.4}
.stat-info small{display:block;font-size:10px;color:#9fb3d3;line-height:1.35;margin-top:3px}

/* Cards: folder edge on the left */
.card{position:relative;overflow:hidden;padding:24px 26px;background:var(--card);border:1px solid var(--line);border-left:4px solid var(--navy-2);border-radius:20px;box-shadow:var(--shadow);margin-bottom:24px;transition:box-shadow .3s}
.card:hover{box-shadow:0 2px 4px rgba(16,37,71,.05),0 18px 40px rgba(16,37,71,.1)}
.card::before{content:"";position:absolute;left:0;top:0;right:0;height:4px;background:linear-gradient(90deg,var(--navy),var(--navy-2) 60%,var(--gold))}
.search-card{border-left-color:var(--gold)}
.deleted-card{border-left-color:var(--stamp)}
.card-heading{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:20px;padding-bottom:16px;border-bottom:1px dashed var(--rule);position:relative}
.card-heading::after{content:"";position:absolute;left:0;bottom:-1px;width:84px;height:2px;border-radius:2px;background:linear-gradient(90deg,var(--gold),var(--gold-2))}
.card-heading-left{display:flex;align-items:center;gap:13px}
.section-icon{width:42px;height:42px;flex-shrink:0;border-radius:12px 12px 12px 4px;display:grid;place-items:center;background:linear-gradient(135deg,var(--navy),var(--navy-2));color:var(--gold-2);font-size:15px;box-shadow:0 8px 18px rgba(13,37,71,.28),inset 0 1px 0 rgba(255,255,255,.18)}
.section-icon.danger{background:linear-gradient(135deg,#8f1f2b,#c93636);color:#ffe3e6}
.section-icon.success{background:linear-gradient(135deg,#0d6b4a,#17a06f);color:#dcfff0}
.card-heading h2{font-family:var(--serif);font-size:16px;font-weight:700;color:var(--ink);line-height:1.3}
.card-heading p{font-size:var(--fs-sm);color:var(--muted);margin-top:1px;max-width:62ch}

/* Forms */
.form-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px}
.field{min-width:0}
.field label{display:block;color:var(--navy-2);font-size:var(--fs-sm);font-weight:700;margin-bottom:6px;letter-spacing:.2px}
.input-wrap{height:42px;display:flex;align-items:center;gap:10px;padding:0 13px;min-width:0;background:#fff;border:1px solid #d9e1ee;border-radius:12px;transition:.2s;box-shadow:inset 0 1px 2px rgba(16,37,71,.04)}
.input-wrap:hover{border-color:var(--gold)}
.input-wrap:focus-within{border-color:var(--blue);background:#fff;box-shadow:0 0 0 4px rgba(37,99,208,.12),0 6px 16px rgba(37,99,208,.08)}
.input-wrap>i{color:#7a8ca8;font-size:12px;flex-shrink:0}
.input-wrap:focus-within>i{color:var(--blue)}
.input-wrap input,.input-wrap select,.input-wrap textarea{width:100%;min-width:0;height:100%;border:0;outline:0;background:transparent;color:var(--ink);font-size:var(--fs-md)}
.input-wrap input::placeholder,.input-wrap textarea::placeholder{color:#9aa8bf}
.input-wrap select{cursor:pointer;appearance:none;-webkit-appearance:none;padding-right:20px;
    background:url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='10' height='6' viewBox='0 0 10 6'><path d='M1 1l4 4 4-4' fill='none' stroke='%237a8ca8' stroke-width='1.6' stroke-linecap='round' stroke-linejoin='round'/></svg>") no-repeat right 2px center}
.input-wrap.textarea{height:auto;align-items:flex-start;padding:11px 13px}
.input-wrap.textarea>i{margin-top:4px}
.input-wrap textarea{height:auto;min-height:84px;resize:vertical;line-height:1.6}
.btn{position:relative;overflow:hidden;min-height:42px;padding:0 20px;border:1px solid transparent;border-radius:12px;display:inline-flex;align-items:center;justify-content:center;gap:8px;font-size:var(--fs-md);font-weight:600;transition:.2s}
.btn:active{transform:scale(.98)}
.btn-primary{background:linear-gradient(135deg,var(--navy),var(--navy-2));color:#fff;box-shadow:0 6px 16px rgba(13,37,71,.25)}
.btn-primary:hover{transform:translateY(-2px);box-shadow:0 10px 22px rgba(13,37,71,.3)}
.btn-primary i{color:var(--gold-2)}
.btn-primary::after{content:"";position:absolute;top:0;left:-70%;width:50%;height:100%;background:linear-gradient(100deg,transparent,rgba(255,255,255,.28),transparent);transform:skewX(-20deg);transition:left .6s}
.btn-primary:hover::after{left:130%}
.btn-outline{background:#fff;border-color:#d3dcea;color:#33466a}
.btn-outline:hover{background:var(--bg);border-color:var(--gold);color:var(--navy)}
.btn-danger{background:linear-gradient(135deg,#a82633,var(--red));color:#fff;box-shadow:0 6px 16px rgba(201,54,54,.28)}
.btn-danger:hover{transform:translateY(-2px);box-shadow:0 10px 22px rgba(201,54,54,.34)}
.btn-success{background:linear-gradient(135deg,#0d6b4a,#17a06f);color:#fff;box-shadow:0 6px 16px rgba(15,107,75,.28)}
.btn-success:hover{transform:translateY(-2px);box-shadow:0 10px 22px rgba(15,107,75,.34)}
.search-actions{display:flex;justify-content:flex-end;gap:10px;margin-top:20px;padding-top:18px;border-top:1px dashed var(--rule)}
.inline-search{max-width:340px;width:100%}

/* Results */
.results-heading{display:flex;justify-content:space-between;align-items:center;gap:14px;margin-bottom:16px;flex-wrap:wrap}
.results-heading .card-heading{margin-bottom:0;padding-bottom:0;border-bottom:0}
.results-heading .card-heading::after{display:none}
.toolbar{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:14px}

/* Count chip: label sticker */
.count-chip{display:inline-flex;align-items:center;gap:7px;padding:6px 14px;background:#fff;color:#6b5326;border:1px solid #e8d9b0;border-left:4px solid var(--gold);border-radius:6px;font-size:var(--fs-sm);font-weight:700}
.count-chip.red{color:#9b2433;border-color:#f0c2c8;border-left-color:var(--stamp)}

/* Table: ledger with department colour edge */
.table-wrapper{overflow:auto;border:1px solid var(--line);border-radius:12px;background:#fff;box-shadow:inset 0 0 0 1px var(--line),0 4px 14px rgba(16,37,71,.04)}
.results-scroll{max-height:520px;scrollbar-width:thin;scrollbar-color:#b4c3dc transparent}
table{width:100%;border-collapse:collapse;white-space:nowrap;font-size:var(--fs-sm)}
thead{background:linear-gradient(180deg,#f1f5fb,#e4ebf6)}
th{text-align:left;padding:13px 14px;color:#26406b;font-size:12px;font-weight:700;letter-spacing:.2px;border-bottom:2px solid #d5deed}
.results-scroll thead th{position:sticky;top:0;z-index:2;background:#e9eff8}
td{padding:12px 14px;color:#4b5d7c;border-top:1px dashed #e9e2d0;font-size:var(--fs-sm)}
tbody tr:nth-child(even){background:#fcfaf4}
tbody tr{transition:background .15s}
tbody tr:hover{background:linear-gradient(90deg,#fff7e6,#f1f6ff 40%)}
td:first-child{color:var(--navy-2);font-weight:700}
tbody tr:hover td:first-child{box-shadow:inset 7px 0 0 var(--row-ink,var(--gold))}
tbody tr[class*="dept-row-"] td:first-child{box-shadow:inset 4px 0 0 var(--row-ink,var(--gold))}
tbody tr[class*="dept-row-"]:hover td:first-child{box-shadow:inset 7px 0 0 var(--row-ink,var(--gold))}
.dept-row-Institutions{--row-ink:var(--ink-inst)}
.dept-row-Councils{--row-ink:var(--ink-coun)}
.dept-row-Accounts{--row-ink:var(--ink-acct)}
.td-actions{display:flex;gap:6px}
.file-name{display:flex;align-items:center;gap:10px;color:var(--ink);font-weight:600}
.file-icon{font-size:13px;color:#2f6fe0;background:#eaf1fd;border-radius:8px;width:28px;height:28px;display:inline-grid;place-items:center;flex-shrink:0}
.file-icon.red{color:var(--red);background:#fdecee}
.file-icon.user{color:var(--navy-2);background:#e8eef9}
.reason-cell{white-space:normal;min-width:240px;max-width:380px;color:#33466a;line-height:1.6}
.no-results{padding:36px 15px;text-align:center;color:var(--muted)}
.no-results i{display:block;margin:0 auto 10px;font-size:32px;color:#b9c6dc}
.dept-pill,.role-pill{display:inline-block;padding:3px 11px;border-radius:20px;font-size:11px;font-weight:600;border:1px solid transparent}
.dept-pill::before{content:"";display:inline-block;width:6px;height:6px;border-radius:50%;background:currentColor;margin-right:6px;vertical-align:1px}
.dept-Institutions{background:#e8f0ff;color:#1f4fa8;border-color:#cfdffc}
.dept-Councils{background:#fdf3dc;color:#8a6420;border-color:#f0dcb0}
.dept-Accounts{background:#e3f6ee;color:#11704f;border-color:#c5ead9}
.role-user{background:#e8f0ff;color:#1f4fa8;border-color:#cfdffc}
.role-subject{background:#f1e9fb;color:#5b2f99;border-color:#dccaf3}

/* Row buttons: smaller, quieter */
.row-btn{display:inline-flex;align-items:center;gap:6px;height:30px;padding:0 12px;border-radius:6px;border:1px solid #d3dcea;background:#fff;color:#27487a;font-size:var(--fs-xs);font-weight:600;transition:.2s;box-shadow:0 1px 2px rgba(16,37,71,.06)}
.row-btn i{font-size:11px;color:var(--gold)}
.row-btn:hover{transform:translateY(-1px);box-shadow:0 6px 12px rgba(16,37,71,.14);background:linear-gradient(135deg,var(--navy),var(--navy-2));border-color:var(--navy);color:#fff}
.row-btn:hover i{color:var(--gold-2)}
.row-btn.view{border-color:#bfd2f3;background:#fff;color:var(--blue)}
.row-btn.view i{color:var(--blue)}
.row-btn.view:hover{background:var(--blue);border-color:var(--blue);color:#fff}
.row-btn.view:hover i{color:#fff}
.row-btn.del{border-color:#e3c9cd;color:var(--red)}
.row-btn.del i{color:var(--red)}
.row-btn.del:hover{background:var(--red);border-color:var(--red);color:#fff}
.row-btn.del:hover i{color:#fff}
.row-btn.restore,.row-btn.ok{border-color:#c5ead9;background:#e9f8f1;color:#11704f}
.row-btn.restore i,.row-btn.ok i{color:#11704f}
.row-btn.restore:hover,.row-btn.ok:hover{background:#11704f;border-color:#11704f;color:#fff}
.row-btn.restore:hover i,.row-btn.ok:hover i{color:#fff}
.row-btn[disabled]{opacity:.45;cursor:not-allowed;pointer-events:none}

/* Modals */
.modal{position:fixed;inset:0;z-index:60;display:flex;align-items:center;justify-content:center;padding:20px;background:rgba(7,27,55,.6);backdrop-filter:blur(3px)}
.dialog{width:min(1000px,100%);height:min(90vh,840px);display:flex;flex-direction:column;overflow:hidden;background:#fff;border-radius:20px;box-shadow:0 24px 60px rgba(7,27,55,.4);animation:pop .25s ease both}
.dialog.small{width:min(520px,100%);height:auto;max-height:92vh}
/* Review dialog: wider, with the document preview inside */
.dialog.review{width:min(760px,100%)}
.ap-preview-head{display:flex;justify-content:space-between;align-items:center;gap:10px;margin-bottom:6px}
.ap-preview-head label{color:var(--navy-2);font-size:var(--fs-sm);font-weight:700}
.ap-preview{height:340px;border:1px solid var(--line);border-radius:12px;overflow:hidden;background:#e9eef6;display:flex;align-items:center;justify-content:center}
.ap-preview object,.ap-preview iframe{width:100%;height:100%;border:0;background:#fff}
.ap-preview img{max-width:100%;max-height:100%;object-fit:contain;display:block}
@keyframes pop{from{opacity:0;transform:translateY(14px) scale(.98)}to{opacity:1;transform:none}}
@keyframes fadeUp{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:none}}
@keyframes floatGlow{0%,100%{transform:translate(0,0)}50%{transform:translate(-18px,14px)}}
.dialog-head{display:flex;justify-content:space-between;align-items:center;gap:12px;padding:16px 20px;border-bottom:1px solid var(--line);position:relative}
.dialog-head::after{content:"";position:absolute;left:0;right:0;bottom:-1px;height:4px;background:linear-gradient(90deg,var(--navy),var(--navy-2) 60%,var(--gold))}
.dialog-head.danger::after{background:linear-gradient(90deg,#8f1f2b,var(--red) 60%,var(--gold))}
.dialog-head.success::after{background:linear-gradient(90deg,#0d6b4a,#17a06f 60%,var(--gold))}
.dialog-title{display:flex;align-items:center;gap:12px;min-width:0}
.dialog-title h3{font-family:var(--serif);font-size:var(--fs-xl);font-weight:700;line-height:1.3;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.dialog-title p{font-size:var(--fs-xs);color:var(--muted)}
.dialog-close{width:36px;height:36px;flex-shrink:0;border:1px solid var(--line);border-radius:10px;background:#fff;color:#5e7290;transition:.2s}
.dialog-close:hover{background:var(--red);border-color:var(--red);color:#fff}
.dialog-body{padding:20px;display:grid;gap:16px;overflow:auto}
.dialog-foot{display:flex;justify-content:flex-end;flex-wrap:wrap;gap:10px;padding:14px 20px;border-top:1px solid var(--line)}
.dialog-foot a.btn{text-decoration:none}
.target-box{display:flex;align-items:center;gap:12px;padding:12px 14px;border-radius:12px;background:#fdf3f4;border:1px solid #f0cdd1;color:#7d1f2b}
.target-box.success{background:#effaf5;border-color:#c5ead9;color:#11704f}
.target-box strong{display:block;color:var(--ink);font-size:var(--fs-md)}
.target-box small{font-size:var(--fs-xs);color:#8a5a60}
.target-box.success small{color:#4f7a68}
/* Warning note: pinned slip */
.warn-note{display:flex;align-items:flex-start;gap:10px;padding:12px 14px;border-radius:6px;background:#fffaf0;border:1px solid #f0dcb0;border-left:4px solid var(--gold);color:#6b5326;font-size:var(--fs-sm);line-height:1.7}
.warn-note i{color:var(--gold);margin-top:3px}
.err-text{color:var(--red);font-size:var(--fs-xs);margin-top:5px}
.doc-meta{display:flex;flex-wrap:wrap;gap:8px;padding:12px 20px;background:#f8fafd;border-bottom:1px solid var(--line)}
.doc-meta span{display:inline-flex;align-items:center;gap:6px;padding:4px 11px;border-radius:20px;background:#fff;border:1px solid var(--line);color:#33466a;font-size:var(--fs-xs);font-weight:600}
.doc-meta span i{color:var(--gold)}
.doc-meta.plain{padding:0;background:none;border:0}
.doc-body{flex:1;min-height:0;background:#e9eef6;display:flex;align-items:center;justify-content:center;overflow:auto}
.doc-body object,.doc-body iframe{width:100%;height:100%;border:0;background:#fff}
.doc-body img{max-width:100%;max-height:100%;object-fit:contain;display:block}
.doc-fallback{max-width:380px;padding:30px 20px;text-align:center;color:var(--muted)}
.doc-fallback i{font-size:40px;color:#a9b8d0;margin-bottom:12px}

/* Toast */
.toast{position:fixed;right:22px;bottom:22px;z-index:80;display:flex;align-items:center;gap:10px;padding:13px 18px;border-radius:12px;background:#0f6b4b;color:#fff;font-size:var(--fs-sm);font-weight:600;box-shadow:0 12px 30px rgba(15,107,75,.35);animation:pop .25s ease both}
.toast.err{background:var(--red);box-shadow:0 12px 30px rgba(201,54,54,.35)}

.footer{display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;margin-top:6px;padding:16px 4px 0;border-top:1px dashed var(--rule);color:#8796ae;font-size:var(--fs-xs)}
::-webkit-scrollbar{width:10px;height:10px}
::-webkit-scrollbar-thumb{background:#c3cfe3;border-radius:10px;border:2px solid transparent;background-clip:content-box}

/* Desktop collapse */
@media (min-width:701px){
    .app.sidebar-collapsed .sidebar{width:0}
    .app.sidebar-collapsed .sidebar>*{visibility:hidden}
}
@media (max-width:1150px){
    .sidebar{width:248px}
    .content{padding:20px 18px 28px}
    .topbar{padding:10px 18px}
    .hero{gap:16px;padding:20px}
    .hero>.hero-art{opacity:.1;max-width:60%}
    .hero-stats{grid-template-columns:repeat(3,minmax(0,1fr))}
    .form-grid{grid-template-columns:repeat(2,minmax(0,1fr))}
}
.sidebar-overlay{display:none}
@media (max-width:700px){
    .sidebar{position:fixed;left:-290px;top:0;bottom:0;width:268px;height:auto;min-height:100%;box-shadow:10px 0 30px rgba(0,25,60,.3);transition:left .28s ease}
    .sidebar.open{left:0}
    .sidebar-overlay.show{display:block;position:fixed;inset:0;background:rgba(7,27,55,.55);z-index:15;backdrop-filter:blur(2px)}
    .topbar{min-height:58px;padding:8px 12px}
    .topbar-left{gap:10px}
    .menu-toggle{width:36px;height:36px}
    .title-icon{width:34px;height:34px;font-size:14px;border-radius:10px}
    .title-text{padding-left:10px}
    .title-text h1{font-size:16px}
    .title-text>span{display:none}
    .profile-details strong{font-size:var(--fs-xs);max-width:90px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
    .profile-popover{width:210px;top:50px}
    .content{padding:16px 12px 24px}
    .hero{padding:20px 16px;border-radius:16px;margin-bottom:16px}
    .hero>.hero-art{display:none}
    .hero h1{font-size:22px}
    .hero-stats{grid-template-columns:repeat(2,minmax(0,1fr));gap:8px}
    .stat-card{display:flex;align-items:center;gap:10px;padding:10px}
    .stat-icon{width:28px;height:28px;flex-shrink:0;margin-bottom:0}
    .stat-info h2{font-size:18px}
    .stat-info span{font-size:10px;margin-top:2px}
    .stat-info small{font-size:9px}
    .card{padding:18px 13px;border-radius:16px;margin-bottom:16px;border-left-width:3px}
    .card-heading{margin-bottom:16px;padding-bottom:12px}
    .card-heading h2{font-size:var(--fs-lg)}
    .section-icon{width:36px;height:36px}
    .form-grid{grid-template-columns:1fr;gap:13px}
    .input-wrap{height:44px}
    .input-wrap.textarea{height:auto}
    .search-actions{gap:8px}
    .search-actions .btn{flex:1;padding:0 10px}
    .inline-search{max-width:none}
    .results-scroll{max-height:440px}
    .modal{padding:0}
    .dialog{height:100%;border-radius:0}
    .dialog.small{height:auto;max-height:100%;border-radius:16px}
    .modal.center-sm{padding:12px}
    .dialog-foot .btn{flex:1;padding:0 10px}
    .ap-preview{height:260px}
    .footer{justify-content:center;text-align:center;font-size:10px}
}
@media (max-width:380px){.hero-stats{grid-template-columns:1fr}}
@media (prefers-reduced-motion:reduce){*{animation:none!important;transition:none!important}}

/* Registration requests + password section */
.role-admin{background:#fdeaea;color:#8e271d;border-color:#f3c2bc}
.password-card{border-left-color:#7a4fb0}
.password-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px}
.alert{display:flex;align-items:flex-start;gap:9px;padding:10px 13px;border-radius:10px;font-size:var(--fs-sm);line-height:1.55;margin-bottom:14px}
.alert i{margin-top:2px}
.alert-success{background:#e9f8f1;color:#11704f;border-left:3px solid #17946a}
.alert-error{background:#fdecea;color:#8e271d;border-left:3px solid var(--red)}
@media (max-width:1150px){.password-grid{grid-template-columns:1fr 1fr}}
@media (max-width:700px){.password-grid{grid-template-columns:1fr;gap:13px}}

/* Print */
.print-only{display:none}
@media print{
    body{background:#fff}
    .app,.modal,.toast{display:none!important}
    .print-only{display:block;padding:10px}
    .print-only h2{font-size:16px;margin-bottom:2px}
    .print-only p{font-size:11px;margin-bottom:6px;color:#333}
    .print-only table{min-width:0;white-space:normal;font-size:10px}
    .print-only th,.print-only td{border:1px solid #999;padding:5px 6px;color:#000}
}

/* ===== File record preview (View button) ===== */
.dialog.record{width:min(900px,100%);height:min(94vh,960px)}
#docViewer .doc-body{padding:16px;background:#dfe5ef;align-items:stretch}
#docViewer .doc-body iframe.doc-frame{width:100%;max-width:794px;min-height:480px;border:0;background:#fff;box-shadow:0 6px 24px rgba(16,37,71,.22)}
@media (max-width:700px){#docViewer .doc-body{padding:8px}}
</style>
</head>

<body>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="app" id="app">

    <!-- SIDEBAR -->
    <aside class="sidebar" id="sidebar">
        <div class="brand">
            <div class="logo-ring">
                <div class="logo-box">
                    <img src="logo.webp" alt="Council Logo" class="logo" onerror="this.parentElement.style.display='none'">
                </div>
            </div>
            <div class="brand-text">
                <div class="sinhala">දකුණු පළාත් සභා<br>ලේකම් කාර්යාලය</div>
                <div class="english">Southern Provincial Council Secretariat</div>
                <div class="system-name"><i class="fa-solid fa-box-archive"></i> Archives File Management System</div>
            </div>
        </div>

        <div class="sidebar-label">ADMIN MENU</div>

        <nav class="sidebar-menu">
            <button type="button" class="nav-link active" data-section="files"><i class="fa-solid fa-house"></i><span>Dashboard</span></button>
            <button type="button" class="nav-link" data-section="approvals"><i class="fa-solid fa-clipboard-check"></i><span>Pending Approvals</span><span class="nav-badge" id="approvalBadge" hidden>0</span></button>
            <button type="button" class="nav-link" data-section="registrations"><i class="fa-solid fa-user-check"></i><span>Registration Requests</span><span class="nav-badge" id="registrationBadge" hidden>0</span></button>
            <button type="button" class="nav-link" data-section="deleted"><i class="fa-solid fa-trash-can-arrow-up"></i><span>Deleted Files</span></button>
            <button type="button" class="nav-link" data-section="users"><i class="fa-solid fa-users"></i><span>Manage Users</span></button>
            <button type="button" class="nav-link" data-section="delusers"><i class="fa-solid fa-user-slash"></i><span>Deleted Users</span></button>
            <button type="button" class="nav-link" data-section="password"><i class="fa-solid fa-key"></i><span>Change Password</span></button>
            <a href="logout.php" class="nav-link logout-link" onclick="return confirm('Are you sure you want to logout?')"><i class="fa-solid fa-right-from-bracket"></i><span>Logout</span></a>
        </nav>

        <div class="sidebar-bottom">
            <div class="sidebar-quote">
                <i class="fa-solid fa-quote-right quote-mark"></i>
                <div class="quote-top"><span>OUR COMMITMENT</span></div>
                <p>Efficient Records for a <strong>Better Tomorrow</strong></p>
                <div class="quote-line"></div>
            </div>
        </div>
    </aside>

    <!-- MAIN -->
    <main class="main">

        <header class="topbar">
            <div class="topbar-left">
                <button class="menu-toggle" id="menuToggle" type="button" aria-label="Toggle menu" aria-expanded="true">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <div class="topbar-title">
                    <div class="title-icon"><i class="fa-solid fa-user-shield"></i></div>
                    <div class="title-text">
                        <h1>Admin</h1>
                        <span>Southern Provincial Council Secretariat</span>
                    </div>
                </div>
            </div>

            <div class="profile">
                <div class="profile-avatar"><i class="fa-solid fa-user-shield"></i></div>
                <div class="profile-details">
                    <strong><?= $adminName ?></strong>
                    <span>Admin Account</span>
                </div>
                <button class="profile-toggle" id="profileToggle" type="button" aria-label="Profile menu" aria-expanded="false">
                    <i class="fa-solid fa-chevron-down"></i>
                </button>
                <div class="profile-popover" id="profilePopover" hidden>
                    <strong><?= $adminName ?></strong>
                    <p><i class="fa-solid fa-user-tag"></i> Admin Account</p>
                    <a href="#" id="profilePasswordLink"><i class="fa-solid fa-lock"></i> Change Password</a>
                    <a href="logout.php" onclick="return confirm('Are you sure you want to logout?')"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
                </div>
            </div>
        </header>

        <div class="content">

            <!-- HERO + STATS -->
            <section class="hero">
                <!-- Archive shelf drawing (decorative watermark) -->
                <svg class="hero-art" viewBox="0 0 360 200" aria-hidden="true" fill="none" stroke="#f0cf8a" stroke-width="2">
                    <path d="M10 10H350M10 100H350M10 190H350M10 10V190M350 10V190"/>
                    <rect x="24" y="44" width="46" height="56"/><rect x="76" y="34" width="46" height="66"/>
                    <rect x="128" y="52" width="46" height="48"/><rect x="190" y="40" width="46" height="60"/>
                    <rect x="242" y="48" width="46" height="52"/><rect x="294" y="36" width="40" height="64"/>
                    <rect x="24" y="134" width="56" height="56"/><rect x="88" y="124" width="46" height="66"/>
                    <rect x="142" y="140" width="46" height="50"/><rect x="204" y="128" width="56" height="62"/>
                    <rect x="268" y="138" width="66" height="52"/>
                    <path d="M34 62h26M86 54h26M138 68h26M200 58h26M252 66h26M34 152h36M98 144h26M152 156h26M214 148h36M278 154h46"/>
                </svg>

                <div>
                    <span class="hero-date"><i class="fa-regular fa-calendar"></i><span id="currentDate"></span></span>
                    <h1>Administration Portal</h1>
                    <p>Approve files sent by the Subject Officer, search archive records, remove or restore files with a recorded reason, and manage registered users.</p>
                </div>
                <div class="hero-stats">
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fa-solid fa-box-archive"></i></div>
                        <div class="stat-info">
                            <h2 id="totalFiles">0</h2>
                            <span>Available Archive Files</span>
                            <small>Files added to the archive</small>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon"><i class="fa-solid fa-building-columns"></i></div>
                        <div class="stat-info">
                            <h2 id="totalDepartments">0</h2>
                            <span>Departments</span>
                            <small>Departments represented</small>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon"><i class="fa-solid fa-magnifying-glass"></i></div>
                        <div class="stat-info">
                            <h2 id="matchingFiles">0</h2>
                            <span>Matching Results</span>
                            <small>Files matching your search</small>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon"><i class="fa-solid fa-clipboard-check"></i></div>
                        <div class="stat-info">
                            <h2 id="statApprovals">0</h2>
                            <span>Pending Approvals</span>
                            <small>Waiting for your decision</small>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon"><i class="fa-solid fa-trash-can"></i></div>
                        <div class="stat-info">
                            <h2 id="statDeleted">0</h2>
                            <span>Deleted Files</span>
                            <small>Removed with a recorded reason</small>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon"><i class="fa-solid fa-users"></i></div>
                        <div class="stat-info">
                            <h2 id="statUsers">0</h2>
                            <span>Registered Users</span>
                            <small>Accounts that can sign in</small>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ===== SECTION: SEARCH FILES ===== -->
            <div id="sec-files">
                <section class="card search-card">
                    <div class="card-heading">
                        <div class="card-heading-left">
                            <div class="section-icon"><i class="fa-solid fa-magnifying-glass"></i></div>
                            <div>
                                <h2>Search Archive Files</h2>
                                <p>Fill in one or more fields. Only the fields you fill are used to filter the files.</p>
                            </div>
                        </div>
                    </div>

                    <form id="searchForm">
                        <div class="form-grid">
                            <div class="field">
                                <label for="searchName">File Name</label>
                                <div class="input-wrap"><i class="fa-regular fa-file-lines"></i><input type="text" id="searchName" placeholder="Enter file name or part of it..."></div>
                            </div>
                            <div class="field">
                                <label for="searchDepartment">Department</label>
                                <div class="input-wrap">
                                    <i class="fa-solid fa-building-columns"></i>
                                    <select id="searchDepartment">
                                        <option value="">All Departments</option>
                                        <option value="Institutions">ආයතන(Institutions)</option>
                                        <option value="Councils">සභා(Councils)</option>
                                        <option value="Accounts">ගිණුම්(Accounts)</option>
                                    </select>
                                </div>
                            </div>
                            <div class="field">
                                <label for="searchSubject">Subject Number</label>
                                <div class="input-wrap"><i class="fa-solid fa-hashtag"></i><input type="text" id="searchSubject" placeholder="Enter subject number..."></div>
                            </div>
                            <div class="field">
                                <label for="searchArchive">Archive Number</label>
                                <div class="input-wrap"><i class="fa-solid fa-box-archive"></i><input type="text" id="searchArchive" placeholder="Enter archive number..."></div>
                            </div>
                        </div>

                        <div class="search-actions">
                            <button class="btn btn-outline" type="button" id="clearSearch"><i class="fa-solid fa-rotate-left"></i> Clear Filters</button>
                            <button class="btn btn-primary" type="submit"><i class="fa-solid fa-magnifying-glass"></i> Search Files</button>
                        </div>
                    </form>
                </section>

                <section class="card">
                    <div class="results-heading">
                        <div class="card-heading">
                            <div class="card-heading-left">
                                <div class="section-icon"><i class="fa-solid fa-table-list"></i></div>
                                <div>
                                    <h2>Archive Search Results</h2>
                                    <p>View, print or delete archive records.</p>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="btn btn-outline" id="printAll"><i class="fa-solid fa-print"></i> Print Results</button>
                    </div>

                    <div style="margin-bottom:14px">
                        <span class="count-chip"><i class="fa-solid fa-folder"></i><span id="resultCount">0 files found</span></span>
                    </div>

                    <div class="table-wrapper results-scroll">
                        <table id="resultsTable">
                            <thead>
                                <tr>
                                    <th>No.</th><th>File Name</th><th>Department</th><th>Subject Number</th><th>File Number</th>
                                    <th>Archive Number</th><th>Start Date</th><th>End Date</th><th>Pages</th><th>Added Date</th><th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="resultsBody"></tbody>
                        </table>
                        <div class="no-results" id="noResults" hidden>
                            <i class="fa-regular fa-folder-open"></i>
                            <p>No matching archive files found.</p>
                            <p style="font-size:11px;margin-top:4px">Try changing your search filters.</p>
                        </div>
                    </div>
                </section>
            </div>

            <!-- ===== SECTION: PENDING APPROVALS ===== -->
            <div id="sec-approvals" hidden>
                <section class="card search-card">
                    <div class="card-heading">
                        <div class="card-heading-left">
                            <div class="section-icon"><i class="fa-solid fa-clipboard-check"></i></div>
                            <div>
                                <h2>Pending Approvals</h2>
                                <p>Files saved by the Subject Officer with an Archive Number and Archive Tree Number. A file is added to the archive only after you approve it.</p>
                            </div>
                        </div>
                    </div>

                    <div class="toolbar">
                        <span class="count-chip"><i class="fa-solid fa-hourglass-half"></i><span id="approvalCount">0 files waiting</span></span>
                        <div class="input-wrap inline-search">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <input type="text" id="approvalFilter" placeholder="Search by name, archive number or subject...">
                        </div>
                    </div>

                    <div class="table-wrapper results-scroll">
                        <table id="approvalTable">
                            <thead>
                                <tr>
                                    <th>No.</th><th>Sent Date</th><th>File Name</th><th>Department</th><th>Subject Number</th><th>File Number</th>
                                    <th>Archive Number</th><th>Archive Tree Number</th><th>Pages</th><th>Sent By</th><th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="approvalBody"></tbody>
                        </table>
                        <div class="no-results" id="approvalEmpty" hidden>
                            <i class="fa-regular fa-folder-open"></i>
                            <p id="approvalEmptyMsg">No files are waiting for approval.</p>
                        </div>
                    </div>
                </section>
            </div>

            <!-- ===== SECTION: DELETED FILES ===== -->
            <div id="sec-deleted" hidden>
                <section class="card deleted-card">
                    <div class="results-heading">
                        <div class="card-heading">
                            <div class="card-heading-left">
                                <div class="section-icon danger"><i class="fa-solid fa-trash-can-arrow-up"></i></div>
                                <div>
                                    <h2>Deleted Files</h2>
                                    <p>Files removed from the archive, with the date and reason for each deletion. Use Restore to bring a file back.</p>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="btn btn-outline" id="printDeleted"><i class="fa-solid fa-print"></i> Print List</button>
                    </div>

                    <div class="toolbar">
                        <span class="count-chip red"><i class="fa-solid fa-trash-can"></i><span id="deletedCount">0 deleted files</span></span>
                        <div class="input-wrap inline-search">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <input type="text" id="deletedFilter" placeholder="Search by name, archive number or reason...">
                        </div>
                    </div>

                    <div class="table-wrapper results-scroll">
                        <table id="deletedTable">
                            <thead>
                                <tr>
                                    <th>No.</th><th>File Name</th><th>Department</th><th>Subject Number</th><th>File Number</th>
                                    <th>Archive Number</th><th>Deleted Date</th><th>Reason for Deletion</th><th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="deletedBody"></tbody>
                        </table>
                        <div class="no-results" id="deletedEmpty" hidden>
                            <i class="fa-regular fa-folder-open"></i>
                            <p id="deletedEmptyMsg">No files have been deleted.</p>
                        </div>
                    </div>
                </section>
            </div>

            <!-- ===== SECTION: REGISTRATION REQUESTS ===== -->
            <div id="sec-registrations" hidden>
                <section class="card">
                    <div class="card-heading">
                        <div class="card-heading-left">
                            <div class="section-icon"><i class="fa-solid fa-user-check"></i></div>
                            <div>
                                <h2>Registration Requests</h2>
                                <p>New user, subject officer and admin accounts wait here until you approve their login access.</p>
                            </div>
                        </div>
                    </div>

                    <div class="toolbar">
                        <span class="count-chip"><i class="fa-solid fa-user-clock"></i><span id="registrationCount">0 requests</span></span>
                        <div class="input-wrap inline-search">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <input type="text" id="registrationFilter" placeholder="Search by name, username or email...">
                        </div>
                    </div>

                    <div class="table-wrapper results-scroll">
                        <table id="registrationsTable">
                            <thead>
                                <tr><th>No.</th><th>Full Name</th><th>Username</th><th>Role</th><th>Department</th><th>Email</th><th>Registered Date</th><th>Action</th></tr>
                            </thead>
                            <tbody id="registrationsBody"></tbody>
                        </table>
                        <div class="no-results" id="registrationsEmpty" hidden>
                            <i class="fa-regular fa-user"></i>
                            <p id="registrationsEmptyMsg">No pending registration requests.</p>
                        </div>
                    </div>
                </section>
            </div>

            <!-- ===== SECTION: USERS ===== -->
            <div id="sec-users" hidden>
                <section class="card">
                    <div class="card-heading">
                        <div class="card-heading-left">
                            <div class="section-icon"><i class="fa-solid fa-users"></i></div>
                            <div>
                                <h2>Registered Users</h2>
                                <p>Remove accounts that no longer need access to the system.</p>
                            </div>
                        </div>
                    </div>

                    <div class="toolbar">
                        <span class="count-chip"><i class="fa-solid fa-user-group"></i><span id="userCount">0 users</span></span>
                        <div class="input-wrap inline-search">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <input type="text" id="userFilter" placeholder="Search by name or username...">
                        </div>
                    </div>

                    <div class="table-wrapper results-scroll">
                        <table id="usersTable">
                            <thead>
                                <tr><th>No.</th><th>Full Name</th><th>Username</th><th>Role</th><th>Registered Date</th><th>Action</th></tr>
                            </thead>
                            <tbody id="usersBody"></tbody>
                        </table>
                        <div class="no-results" id="usersEmpty" hidden>
                            <i class="fa-regular fa-user"></i>
                            <p id="usersEmptyMsg">No registered users.</p>
                        </div>
                    </div>
                </section>
            </div>

            <!-- ===== SECTION: DELETED USERS ===== -->
            <div id="sec-delusers" hidden>
                <section class="card deleted-card">
                    <div class="results-heading">
                        <div class="card-heading">
                            <div class="card-heading-left">
                                <div class="section-icon danger"><i class="fa-solid fa-user-slash"></i></div>
                                <div>
                                    <h2>Deleted Users</h2>
                                    <p>User accounts removed by the administrator, with the date of deletion.</p>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="btn btn-outline" id="printDelUsers"><i class="fa-solid fa-print"></i> Print List</button>
                    </div>

                    <div class="toolbar">
                        <span class="count-chip red"><i class="fa-solid fa-user-slash"></i><span id="delUserCount">0 deleted users</span></span>
                        <div class="input-wrap inline-search">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <input type="text" id="delUserFilter" placeholder="Search by name or username...">
                        </div>
                    </div>

                    <div class="table-wrapper results-scroll">
                        <table id="delUsersTable">
                            <thead>
                                <tr><th>No.</th><th>Full Name</th><th>Username</th><th>Role</th><th>Registered Date</th><th>Deleted Date</th></tr>
                            </thead>
                            <tbody id="delUsersBody"></tbody>
                        </table>
                        <div class="no-results" id="delUsersEmpty" hidden>
                            <i class="fa-regular fa-user"></i>
                            <p id="delUsersEmptyMsg">No users have been deleted.</p>
                        </div>
                    </div>
                </section>
            </div>

            <!-- ===== SECTION: CHANGE PASSWORD ===== -->
            <div id="sec-password" hidden>
                <section class="card password-card">
                    <div class="card-heading">
                        <div class="card-heading-left">
                            <div class="section-icon"><i class="fa-solid fa-key"></i></div>
                            <div>
                                <h2>Change Password</h2>
                                <p>Enter your current password, then choose a new password of at least 6 characters.</p>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-success" id="passwordAlert" hidden>
                        <i class="fa-solid fa-circle-check"></i>
                        <span id="passwordAlertText"></span>
                    </div>

                    <form id="passwordForm">
                        <div class="password-grid">
                            <div class="field">
                                <label for="currentPassword">Current Password</label>
                                <div class="input-wrap"><i class="fa-solid fa-lock"></i><input type="password" id="currentPassword" placeholder="Enter current password..." autocomplete="current-password" required></div>
                            </div>
                            <div class="field">
                                <label for="newPassword">New Password</label>
                                <div class="input-wrap"><i class="fa-solid fa-key"></i><input type="password" id="newPassword" placeholder="At least 6 characters..." autocomplete="new-password" minlength="6" required></div>
                            </div>
                            <div class="field">
                                <label for="confirmPassword">Confirm New Password</label>
                                <div class="input-wrap"><i class="fa-solid fa-key"></i><input type="password" id="confirmPassword" placeholder="Re-enter new password..." autocomplete="new-password" minlength="6" required></div>
                            </div>
                        </div>

                        <div class="search-actions">
                            <button type="reset" class="btn btn-outline"><i class="fa-solid fa-rotate-left"></i> Clear Form</button>
                            <button type="submit" class="btn btn-primary" id="passwordSave"><i class="fa-solid fa-floppy-disk"></i> Update Password</button>
                        </div>
                    </form>
                </section>
            </div>

            <footer class="footer">
                <span>© <span id="footerYear">2026</span> Southern Provincial Council. All Rights Reserved.</span>
                <span>Archives File Management System</span>
            </footer>
        </div>
    </main>
</div>

<!-- APPROVAL MODAL (with document preview) -->
<div class="modal center-sm" id="approvalModal" hidden>
    <div class="dialog small review" role="dialog" aria-modal="true" aria-labelledby="apTitle">
        <div class="dialog-head success">
            <div class="dialog-title">
                <div class="section-icon success"><i class="fa-solid fa-clipboard-check"></i></div>
                <div>
                    <h3 id="apTitle">Review File</h3>
                    <p>Approve to add it to the archive, or reject with a reason.</p>
                </div>
            </div>
            <button type="button" class="dialog-close" data-close="approvalModal" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="dialog-body">
            <div class="target-box success">
                <i class="fa-solid fa-file-circle-check" style="font-size:20px"></i>
                <div><strong id="apName"></strong><small id="apMeta"></small></div>
            </div>

            <div class="doc-meta plain" id="apInfo"></div>

            <!-- Document preview -->
            <div>
                <div class="ap-preview-head">
                    <label>Submitted Document</label>
                    <button type="button" class="row-btn view" id="apOpenFull"><i class="fa-solid fa-expand"></i> Full Screen</button>
                </div>
                <div class="ap-preview" id="apPreview"></div>
            </div>

            <div class="field">
                <label for="apReason">Reason (required only when rejecting)</label>
                <div class="input-wrap textarea"><i class="fa-regular fa-comment-dots"></i><textarea id="apReason" placeholder="Why is this file being returned to the Subject Officer?"></textarea></div>
                <div class="err-text" id="apError" hidden></div>
            </div>

            <div class="warn-note"><i class="fa-solid fa-circle-info"></i><span>Approve: the file is added to the archive. Reject: the file goes back to the Subject Officer with your reason.</span></div>
        </div>
        <div class="dialog-foot">
            <button type="button" class="btn btn-outline" data-close="approvalModal"><i class="fa-solid fa-xmark"></i> Cancel</button>
            <button type="button" class="btn btn-danger" id="apReject"><i class="fa-solid fa-ban"></i> Reject</button>
            <button type="button" class="btn btn-success" id="apApprove"><i class="fa-solid fa-circle-check"></i> Approve &amp; Add to Archive</button>
        </div>
    </div>
</div>

<!-- DELETE FILE MODAL -->
<div class="modal center-sm" id="deleteModal" hidden>
    <form class="dialog small" id="deleteForm" role="dialog" aria-modal="true" aria-labelledby="delTitle" novalidate>
        <div class="dialog-head danger">
            <div class="dialog-title">
                <div class="section-icon danger"><i class="fa-solid fa-trash-can"></i></div>
                <div>
                    <h3 id="delTitle">Delete Archive File</h3>
                    <p>The reason and date are saved in the Deleted Files list.</p>
                </div>
            </div>
            <button type="button" class="dialog-close" data-close="deleteModal" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <div class="dialog-body">
            <div class="target-box">
                <i class="fa-solid fa-file-circle-xmark" style="font-size:20px"></i>
                <div><strong id="delName"></strong><small id="delMeta"></small></div>
            </div>

            <div class="field">
                <label for="delDate">Deletion Date</label>
                <div class="input-wrap"><i class="fa-regular fa-calendar"></i><input type="date" id="delDate" required></div>
            </div>

            <div class="field">
                <label for="delReason">Reason for Deletion</label>
                <div class="input-wrap textarea"><i class="fa-regular fa-comment-dots"></i><textarea id="delReason" placeholder="Explain why this file is being removed from the archive..." required></textarea></div>
                <div class="err-text" id="delError" hidden></div>
            </div>

            <div class="warn-note"><i class="fa-solid fa-triangle-exclamation"></i><span>The file is removed from search results for all users and moves to the Deleted Files list.</span></div>
        </div>

        <div class="dialog-foot">
            <button type="button" class="btn btn-outline" data-close="deleteModal"><i class="fa-solid fa-xmark"></i> Cancel</button>
            <button type="submit" class="btn btn-danger"><i class="fa-solid fa-trash-can"></i> Delete File</button>
        </div>
    </form>
</div>

<!-- RESTORE FILE MODAL -->
<div class="modal center-sm" id="restoreModal" hidden>
    <div class="dialog small" role="dialog" aria-modal="true" aria-labelledby="restoreTitle">
        <div class="dialog-head success">
            <div class="dialog-title">
                <div class="section-icon success"><i class="fa-solid fa-trash-can-arrow-up"></i></div>
                <div>
                    <h3 id="restoreTitle">Restore Archive File</h3>
                    <p>The file will return to the archive.</p>
                </div>
            </div>
            <button type="button" class="dialog-close" data-close="restoreModal" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="dialog-body">
            <div class="target-box success">
                <i class="fa-solid fa-file-circle-check" style="font-size:20px"></i>
                <div><strong id="restoreName"></strong><small id="restoreMeta"></small></div>
            </div>
            <div class="warn-note"><i class="fa-solid fa-circle-info"></i><span>The file will appear in search results for all users again and will be removed from the Deleted Files list.</span></div>
        </div>
        <div class="dialog-foot">
            <button type="button" class="btn btn-outline" data-close="restoreModal"><i class="fa-solid fa-xmark"></i> Cancel</button>
            <button type="button" class="btn btn-success" id="confirmRestore"><i class="fa-solid fa-rotate-left"></i> Restore File</button>
        </div>
    </div>
</div>

<!-- DELETE USER MODAL -->
<div class="modal center-sm" id="userModal" hidden>
    <div class="dialog small" role="dialog" aria-modal="true" aria-labelledby="userDelTitle">
        <div class="dialog-head danger">
            <div class="dialog-title">
                <div class="section-icon danger"><i class="fa-solid fa-user-xmark"></i></div>
                <div>
                    <h3 id="userDelTitle">Delete User</h3>
                    <p>This account will no longer be able to sign in.</p>
                </div>
            </div>
            <button type="button" class="dialog-close" data-close="userModal" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="dialog-body">
            <div class="target-box">
                <i class="fa-solid fa-user" style="font-size:20px"></i>
                <div><strong id="userDelName"></strong><small id="userDelMeta"></small></div>
            </div>
            <div class="warn-note"><i class="fa-solid fa-triangle-exclamation"></i><span>This cannot be undone. Files this user already submitted stay in the archive.</span></div>
        </div>
        <div class="dialog-foot">
            <button type="button" class="btn btn-outline" data-close="userModal"><i class="fa-solid fa-xmark"></i> Cancel</button>
            <button type="button" class="btn btn-danger" id="confirmUserDelete"><i class="fa-solid fa-user-xmark"></i> Delete User</button>
        </div>
    </div>
</div>

<!-- DOCUMENT VIEWER (file record) -->
<div class="modal" id="docViewer" hidden>
    <div class="dialog record" role="dialog" aria-modal="true" aria-labelledby="docTitle">
        <div class="dialog-head">
            <div class="dialog-title">
                <div class="section-icon"><i class="fa-solid fa-file-lines"></i></div>
                <div style="min-width:0"><h3 id="docTitle">File Document</h3><p id="docSub"></p></div>
            </div>
            <button type="button" class="dialog-close" data-close="docViewer" aria-label="Close viewer"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="doc-body" id="docBody">
            <iframe class="doc-frame" id="docFrame" title="Archive file document"></iframe>
        </div>
        <div class="dialog-foot">
            <button type="button" class="btn btn-outline" data-close="docViewer"><i class="fa-solid fa-xmark"></i> Close</button>
            <button type="button" class="btn btn-primary" id="docPrintBtn"><i class="fa-solid fa-print"></i> Print</button>
        </div>
    </div>
</div>

<!-- PRINT AREA (used by the list print buttons) -->
<div class="print-only">
    <h2>දකුණු පළාත් සභා ලේකම් කාර්යාලය - <span id="printTitle">Archive Search Results</span></h2>
    <p id="printInfo"></p>
    <table>
        <thead id="printHead"></thead>
        <tbody id="printBody"></tbody>
    </table>
</div>

<div class="toast" id="toast" hidden></div>

<script>
/* ===== DATA: database through api.php ===== */
const API = "api.php";
const USER_NAME = <?= json_encode($adminNameRaw, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>;
const deptLabels = {Institutions:"ආයතන(Institutions)",Councils:"සභා(Councils)",Accounts:"ගිණුම්(Accounts)"};
const roleLabels = {user:"User",subject:"Subject Officer",admin:"Administrator"};

let archiveFiles = [], deletedFiles = [], users = [], deletedUsers = [], filteredFiles = [], approvals = [], pendingUsers = [];
let currentUserId = 0;
let csrfToken = "";

async function api(action, body) {
    const opts = {credentials: "same-origin", headers: {}};
    if (body) {
        opts.method = "POST";
        opts.headers["Content-Type"] = "application/json";
        opts.headers["X-CSRF-Token"] = csrfToken;
        opts.body = JSON.stringify(body);
    }
    const res = await fetch(API + "?action=" + action, opts);
    let data = null;
    try { data = await res.json(); } catch (err) { /* not JSON */ }
    if (data && data.logout) { window.location.href = "index.php"; throw new Error("logged out"); }
    if (!data) throw new Error("The server did not return a valid response (" + res.status + ").");
    if (!res.ok) throw new Error(data.error || "Server error (" + res.status + ")");
    return data;
}

function activeFiles() { return archiveFiles; }

async function loadState() {
    const s = await api("state");
    csrfToken = s.csrf;
    archiveFiles = (s.files || []).map(f => ({...f, pages: Number(f.pages)}));
    deletedFiles = s.deletedFiles || [];
    users = s.users || [];
    deletedUsers = s.deletedUsers || [];
    approvals = s.approvals || [];
    pendingUsers = s.pendingUsers || [];
    currentUserId = Number(s.user && s.user.id) || 0;
    renderAll();
}

async function deleteFileOnServer(file, date, reason) {
    await api("delete_file", {id: file.id, date: date, reason: reason});
}

async function restoreFileOnServer(file) {
    await api("restore_file", {id: file.id});
}

async function deleteUserOnServer(user) {
    await api("delete_user", {id: user.id});
}

async function approveOnServer(item) {
    await api("approve_file", {id: item.id});
}

async function rejectOnServer(item, reason) {
    await api("reject_file", {id: item.id, reason: reason});
}

/* ===== HELPERS ===== */
const $ = id => document.getElementById(id);
function escapeHTML(value) {
    return String(value ?? "").replace(/[&<>"']/g, ch => ({"&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#039;"}[ch]));
}
function deptPill(dep) {
    return `<span class="dept-pill dept-${escapeHTML(dep)}">${escapeHTML(deptLabels[dep] || dep)}</span>`;
}
function toast(message, isError) {
    const t = $("toast");
    t.className = "toast" + (isError ? " err" : "");
    t.innerHTML = `<i class="fa-solid ${isError ? "fa-circle-exclamation" : "fa-circle-check"}"></i><span>${escapeHTML(message)}</span>`;
    t.hidden = false;
    clearTimeout(toast.timer);
    toast.timer = setTimeout(() => { t.hidden = true; }, 4000);
}
const now = new Date();
const localToday = new Date(now.getTime() - now.getTimezoneOffset() * 60000).toISOString().slice(0, 10);
$("currentDate").textContent = now.toLocaleDateString("en-GB", {day:"2-digit", month:"short", year:"numeric"});
$("footerYear").textContent = now.getFullYear();

/* ===== STATS (all cards update automatically) ===== */
function refreshStats() {
    const active = activeFiles();
    $("totalFiles").textContent = active.length;
    $("totalDepartments").textContent = new Set(active.map(f => f.department)).size;
    $("matchingFiles").textContent = filteredFiles.length;
    $("statApprovals").textContent = approvals.length;
    $("statDeleted").textContent = deletedFiles.length;
    $("statUsers").textContent = users.length;
}

/* ===== SECTION SWITCHING ===== */
function showSection(name) {
    ["files", "approvals", "registrations", "deleted", "users", "delusers", "password"].forEach(s => { $("sec-" + s).hidden = (s !== name); });
    document.querySelectorAll(".nav-link[data-section]").forEach(l => l.classList.toggle("active", l.dataset.section === name));
    if (window.innerWidth <= 700) closeSidebar();
    window.scrollTo({top: 0, behavior: "smooth"});
}
document.querySelectorAll(".nav-link[data-section]").forEach(l => l.addEventListener("click", () => showSection(l.dataset.section)));

/* ===== SEARCH (same filters as the user dashboard) ===== */
function runSearch() {
    const name = $("searchName").value.trim().toLowerCase();
    const department = $("searchDepartment").value;
    const subject = $("searchSubject").value.trim().toLowerCase();
    const archive = $("searchArchive").value.trim().toLowerCase();

    filteredFiles = activeFiles().filter(f =>
        (!name || f.name.toLowerCase().includes(name)) &&
        (!department || f.department === department) &&
        (!subject || f.subject.toLowerCase().includes(subject)) &&
        (!archive || f.archive.toLowerCase().includes(archive))
    );
    renderResults();
}
$("searchForm").addEventListener("submit", e => { e.preventDefault(); runSearch(); });
$("clearSearch").addEventListener("click", () => { $("searchForm").reset(); runSearch(); });

function renderResults() {
    const total = filteredFiles.length;
    $("resultCount").textContent = `${total} file${total === 1 ? "" : "s"} found`;
    $("noResults").hidden = total > 0;
    $("resultsTable").style.display = total ? "table" : "none";

    $("resultsBody").innerHTML = filteredFiles.map((f, i) => `
        <tr class="dept-row-${escapeHTML(f.department)}">
            <td>${i + 1}</td>
            <td><div class="file-name"><i class="fa-solid fa-file-lines file-icon"></i><span>${escapeHTML(f.name)}</span></div></td>
            <td>${deptPill(f.department)}</td>
            <td>${escapeHTML(f.subject)}</td>
            <td>${escapeHTML(f.fileNo)}</td>
            <td style="font-weight:700;color:var(--navy-2)">${escapeHTML(f.archive)}</td>
            <td>${escapeHTML(f.start)}</td>
            <td>${escapeHTML(f.end)}</td>
            <td>${escapeHTML(f.pages)}</td>
            <td>${escapeHTML(f.date)}</td>
            <td><div class="td-actions">
                <button type="button" class="row-btn view" data-act="view" data-arc="${escapeHTML(f.archive)}"><i class="fa-solid fa-eye"></i> View</button>
                <button type="button" class="row-btn" data-act="print" data-arc="${escapeHTML(f.archive)}"><i class="fa-solid fa-print"></i> Print</button>
                <button type="button" class="row-btn del" data-act="delete" data-arc="${escapeHTML(f.archive)}"><i class="fa-solid fa-trash-can"></i> Delete</button>
            </div></td>
        </tr>`).join("");
    refreshStats();
}

$("resultsBody").addEventListener("click", e => {
    const btn = e.target.closest("button[data-act]");
    if (!btn) return;
    const file = archiveFiles.find(f => f.archive === btn.dataset.arc);
    if (!file) return;
    if (btn.dataset.act === "view") openViewer(file);
    /* Print ONE file: same document as the View button */
    if (btn.dataset.act === "print") printRecordDocument(file);
    if (btn.dataset.act === "delete") openDelete(file);
});

/* ===== PENDING APPROVALS (files sent by the Subject Officer) ===== */
let fileInReview = null;

function renderApprovals() {
    const q = $("approvalFilter").value.trim().toLowerCase();
    const list = approvals.filter(f => !q ||
        String(f.name).toLowerCase().includes(q) || String(f.archive).toLowerCase().includes(q) ||
        String(f.subject).toLowerCase().includes(q));

    $("approvalCount").textContent = `${list.length} file${list.length === 1 ? "" : "s"} waiting`;
    $("approvalEmpty").hidden = list.length > 0;
    $("approvalEmptyMsg").textContent = approvals.length ? "No files match your search." : "No files are waiting for approval.";
    $("approvalTable").style.display = list.length ? "table" : "none";
    $("approvalBadge").hidden = approvals.length === 0;
    $("approvalBadge").textContent = approvals.length;

    $("approvalBody").innerHTML = list.map((f, i) => `
        <tr class="dept-row-${escapeHTML(f.department)}">
            <td>${i + 1}</td>
            <td>${escapeHTML(f.sentDate)}</td>
            <td><div class="file-name"><i class="fa-solid fa-file-lines file-icon"></i><span>${escapeHTML(f.name)}</span></div></td>
            <td>${deptPill(f.department)}</td>
            <td>${escapeHTML(f.subject)}</td>
            <td>${escapeHTML(f.fileNo)}</td>
            <td style="font-weight:700;color:var(--navy-2)">${escapeHTML(f.archive)}</td>
            <td>${escapeHTML(f.rack)}</td>
            <td>${escapeHTML(f.pages)}</td>
            <td>${escapeHTML(f.sentBy || "-")}</td>
            <td><div class="td-actions">
                <button type="button" class="row-btn view" data-apview="${escapeHTML(f.id)}"><i class="fa-solid fa-eye"></i> View</button>
                <button type="button" class="row-btn ok" data-ap="${escapeHTML(f.id)}"><i class="fa-solid fa-circle-check"></i> Approve</button>
            </div></td>
        </tr>`).join("");
    refreshStats();
}
$("approvalFilter").addEventListener("input", renderApprovals);

$("approvalBody").addEventListener("click", e => {
    // "View" button: open the file record in the viewer
    const viewBtn = e.target.closest("button[data-apview]");
    if (viewBtn) {
        const item = approvals.find(x => String(x.id) === viewBtn.dataset.apview);
        if (item) viewApprovalDoc(item);
        return;
    }

    // "Approve" button: open the review dialog (with document preview)
    const btn = e.target.closest("button[data-ap]");
    if (!btn) return;
    fileInReview = approvals.find(f => String(f.id) === btn.dataset.ap);
    if (!fileInReview) return;
    const f = fileInReview;

    $("apName").textContent = f.name;
    $("apMeta").textContent = `${deptLabels[f.department] || f.department} | ${f.subject} | ${f.archive}`;
    $("apInfo").innerHTML = [
        ["fa-regular fa-file", "File No: " + f.fileNo],
        ["fa-solid fa-box-archive", "Archive No: " + f.archive],
        ["fa-solid fa-table-cells-large", "Archive Tree No: " + f.rack],
        ["fa-regular fa-calendar-days", f.start + " to " + f.end],
        ["fa-solid fa-copy", f.pages + " pages (Right " + f.right + " / Left " + f.left + ")"],
        ["fa-solid fa-user-tie", "Sent by: " + (f.sentBy || "-")],
        ["fa-regular fa-clock", "Sent on: " + f.sentDate]
    ].map(m => `<span><i class="${m[0]}"></i>${escapeHTML(m[1])}</span>`).join("");

    // Document preview inside the dialog
    $("apPreview").innerHTML = docHTML(f.fileUrl, f.name);
    const img = $("apPreview").querySelector("img");
    if (img) img.onerror = () => { $("apPreview").innerHTML = docFallback("The image could not be loaded."); };

    $("apReason").value = "";
    $("apError").hidden = true;
    openModal("approvalModal");
});

$("apOpenFull").addEventListener("click", () => { if (fileInReview) viewApprovalDoc(fileInReview); });

$("apApprove").addEventListener("click", async () => {
    if (!fileInReview) return;
    const target = fileInReview;
    try { await approveOnServer(target); }
    catch (ex) { $("apError").textContent = ex.message; $("apError").hidden = false; return; }
    closeModal("approvalModal");
    fileInReview = null;
    toast('"' + target.name + '" was approved and added to the archive.');
    loadState();
});

$("apReject").addEventListener("click", async () => {
    if (!fileInReview) return;
    const reason = $("apReason").value.trim();
    if (reason.length < 5) {
        $("apError").textContent = "Enter the reason for rejecting this file (at least 5 characters).";
        $("apError").hidden = false;
        return;
    }
    const target = fileInReview;
    try { await rejectOnServer(target, reason); }
    catch (ex) { $("apError").textContent = ex.message; $("apError").hidden = false; return; }
    closeModal("approvalModal");
    fileInReview = null;
    toast('"' + target.name + '" was rejected and sent back to the Subject Officer.');
    loadState();
});

/* ===== DELETE A FILE (reason + date required) ===== */
let fileToDelete = null;

function openDelete(file) {
    fileToDelete = file;
    $("delName").textContent = file.name;
    $("delMeta").textContent = `${deptLabels[file.department] || file.department} | ${file.subject} | ${file.archive}`;
    $("delDate").value = localToday;
    $("delReason").value = "";
    $("delError").hidden = true;
    openModal("deleteModal");
    setTimeout(() => $("delReason").focus(), 50);
}

$("deleteForm").addEventListener("submit", async e => {
    e.preventDefault();
    const date = $("delDate").value;
    const reason = $("delReason").value.trim();
    const err = $("delError");

    if (!date) { err.textContent = "Select the date of deletion."; err.hidden = false; return; }
    if (reason.length < 5) { err.textContent = "Enter the reason for deleting this file (at least 5 characters)."; err.hidden = false; return; }
    if (!fileToDelete) return;

    try {
        await deleteFileOnServer(fileToDelete, date, reason);
    } catch (ex) { err.textContent = ex.message; err.hidden = false; return; }

    const name = fileToDelete.name;
    closeModal("deleteModal");
    fileToDelete = null;
    toast('"' + name + '" was deleted.');
    loadState();
});

/* ===== DELETED FILES LIST ===== */
let shownDeleted = [];
function renderDeleted() {
    const q = $("deletedFilter").value.trim().toLowerCase();
    shownDeleted = deletedFiles.filter(f => !q ||
        f.name.toLowerCase().includes(q) || f.archive.toLowerCase().includes(q) ||
        f.subject.toLowerCase().includes(q) || (f.reason || "").toLowerCase().includes(q));

    $("deletedCount").textContent = `${shownDeleted.length} deleted file${shownDeleted.length === 1 ? "" : "s"}`;
    $("deletedEmpty").hidden = shownDeleted.length > 0;
    $("deletedEmptyMsg").textContent = deletedFiles.length ? "No deleted files match your search." : "No files have been deleted.";
    $("deletedTable").style.display = shownDeleted.length ? "table" : "none";

    $("deletedBody").innerHTML = shownDeleted.map((f, i) => `
        <tr class="dept-row-${escapeHTML(f.department)}">
            <td>${i + 1}</td>
            <td><div class="file-name"><i class="fa-solid fa-file-circle-xmark file-icon red"></i><span>${escapeHTML(f.name)}</span></div></td>
            <td>${deptPill(f.department)}</td>
            <td>${escapeHTML(f.subject)}</td>
            <td>${escapeHTML(f.fileNo)}</td>
            <td style="font-weight:700;color:var(--navy-2)">${escapeHTML(f.archive)}</td>
            <td>${escapeHTML(f.deletedDate)}</td>
            <td class="reason-cell">${escapeHTML(f.reason)}</td>
            <td><button type="button" class="row-btn restore" data-arc="${escapeHTML(f.archive)}"><i class="fa-solid fa-rotate-left"></i> Restore</button></td>
        </tr>`).join("");
    refreshStats();
}
$("deletedFilter").addEventListener("input", renderDeleted);

/* ===== RESTORE A DELETED FILE ===== */
let fileToRestore = null;

$("deletedBody").addEventListener("click", e => {
    const btn = e.target.closest("button.restore");
    if (!btn) return;
    fileToRestore = deletedFiles.find(f => f.archive === btn.dataset.arc);
    if (!fileToRestore) return;
    $("restoreName").textContent = fileToRestore.name;
    $("restoreMeta").textContent = `${deptLabels[fileToRestore.department] || fileToRestore.department} | ${fileToRestore.subject} | ${fileToRestore.archive}`;
    openModal("restoreModal");
});

$("confirmRestore").addEventListener("click", async () => {
    if (!fileToRestore) return;
    const target = fileToRestore;
    try {
        await restoreFileOnServer(target);
    } catch (ex) { toast(ex.message, true); return; }
    closeModal("restoreModal");
    fileToRestore = null;
    toast('"' + target.name + '" was restored to the archive.');
    loadState();
});

/* ===== REGISTRATION REQUESTS ===== */
function renderRegistrations() {
    const q = $("registrationFilter").value.trim().toLowerCase();
    const list = pendingUsers.filter(u => !q ||
        String(u.name).toLowerCase().includes(q) || String(u.username).toLowerCase().includes(q) ||
        String(u.email || "").toLowerCase().includes(q));

    $("registrationCount").textContent = `${list.length} request${list.length === 1 ? "" : "s"}`;
    $("registrationsEmpty").hidden = list.length > 0;
    $("registrationsEmptyMsg").textContent = pendingUsers.length ? "No requests match your search." : "No pending registration requests.";
    $("registrationsTable").style.display = list.length ? "table" : "none";
    $("registrationBadge").hidden = pendingUsers.length === 0;
    $("registrationBadge").textContent = pendingUsers.length;

    $("registrationsBody").innerHTML = list.map((u, i) => `
        <tr>
            <td>${i + 1}</td>
            <td><div class="file-name"><i class="fa-solid fa-user file-icon user"></i><span>${escapeHTML(u.name)}</span></div></td>
            <td>${escapeHTML(u.username)}</td>
            <td><span class="role-pill role-${escapeHTML(u.role)}">${escapeHTML(roleLabels[u.role] || u.role)}</span></td>
            <td>${escapeHTML(deptLabels[u.department] || u.department)}</td>
            <td>${escapeHTML(u.email || "-")}</td>
            <td>${escapeHTML(u.registered)}</td>
            <td><div class="td-actions">
                <button type="button" class="row-btn ok" data-approve-user="${u.id}"><i class="fa-solid fa-circle-check"></i> Approve</button>
                <button type="button" class="row-btn del" data-reject-user="${u.id}"><i class="fa-solid fa-user-xmark"></i> Reject</button>
            </div></td>
        </tr>`).join("");
}
$("registrationFilter").addEventListener("input", renderRegistrations);

$("registrationsBody").addEventListener("click", async e => {
    const btn = e.target.closest("button[data-approve-user], button[data-reject-user]");
    if (!btn) return;
    const id = Number(btn.dataset.approveUser || btn.dataset.rejectUser);
    const account = pendingUsers.find(u => Number(u.id) === id);
    if (!account) return;

    const rejecting = btn.dataset.rejectUser !== undefined;
    if (rejecting && !confirm('Reject the registration of "' + account.name + '"? That account will not be able to log in.')) return;

    btn.disabled = true;
    try {
        await api(rejecting ? "reject_user" : "approve_user", {id: id});
        toast(rejecting
            ? '"' + account.name + '" was rejected.'
            : '"' + account.name + '" was approved and can now log in.');
        loadState();
    } catch (ex) {
        toast(ex.message, true);
        btn.disabled = false;
    }
});

/* ===== USERS ===== */
let userToDelete = null;
function renderUsers() {
    const q = $("userFilter").value.trim().toLowerCase();
    const list = users.filter(u => !q || u.name.toLowerCase().includes(q) || u.username.toLowerCase().includes(q));

    $("userCount").textContent = `${list.length} user${list.length === 1 ? "" : "s"}`;
    $("usersEmpty").hidden = list.length > 0;
    $("usersEmptyMsg").textContent = users.length ? "No users match your search." : "No registered users.";
    $("usersTable").style.display = list.length ? "table" : "none";

    $("usersBody").innerHTML = list.map((u, i) => `
        <tr>
            <td>${i + 1}</td>
            <td><div class="file-name"><i class="fa-solid fa-user file-icon user"></i><span>${escapeHTML(u.name)}</span></div></td>
            <td>${escapeHTML(u.username)}</td>
            <td><span class="role-pill role-${escapeHTML(u.role)}">${escapeHTML(roleLabels[u.role] || u.role)}</span></td>
            <td>${escapeHTML(u.registered)}</td>
            <td>${(u.isSelf || Number(u.id) === currentUserId)
                ? '<span style="color:var(--muted);font-size:10px">Current account</span>'
                : `<button type="button" class="row-btn del" data-uid="${u.id}"><i class="fa-solid fa-user-xmark"></i> Delete</button>`}</td>
        </tr>`).join("");
    refreshStats();
}
$("userFilter").addEventListener("input", renderUsers);

$("usersBody").addEventListener("click", e => {
    const btn = e.target.closest("button[data-uid]");
    if (!btn) return;
    userToDelete = users.find(u => String(u.id) === btn.dataset.uid);
    if (!userToDelete) return;
    $("userDelName").textContent = userToDelete.name;
    $("userDelMeta").textContent = `${userToDelete.username} | ${roleLabels[userToDelete.role] || userToDelete.role}`;
    openModal("userModal");
});

$("confirmUserDelete").addEventListener("click", async () => {
    if (!userToDelete) return;
    const target = userToDelete;
    try {
        await deleteUserOnServer(target);
    } catch (ex) { toast(ex.message, true); return; }
    closeModal("userModal");
    userToDelete = null;
    toast(target.name + " was deleted.");
    loadState();
});

/* ===== DELETED USERS LIST ===== */
let shownDelUsers = [];
function renderDeletedUsers() {
    const q = $("delUserFilter").value.trim().toLowerCase();
    shownDelUsers = deletedUsers.filter(u => !q || u.name.toLowerCase().includes(q) || u.username.toLowerCase().includes(q));

    $("delUserCount").textContent = `${shownDelUsers.length} deleted user${shownDelUsers.length === 1 ? "" : "s"}`;
    $("delUsersEmpty").hidden = shownDelUsers.length > 0;
    $("delUsersEmptyMsg").textContent = deletedUsers.length ? "No deleted users match your search." : "No users have been deleted.";
    $("delUsersTable").style.display = shownDelUsers.length ? "table" : "none";

    $("delUsersBody").innerHTML = shownDelUsers.map((u, i) => `
        <tr>
            <td>${i + 1}</td>
            <td><div class="file-name"><i class="fa-solid fa-user-slash file-icon red"></i><span>${escapeHTML(u.name)}</span></div></td>
            <td>${escapeHTML(u.username)}</td>
            <td><span class="role-pill role-${escapeHTML(u.role)}">${escapeHTML(roleLabels[u.role] || u.role)}</span></td>
            <td>${escapeHTML(u.registered)}</td>
            <td>${escapeHTML(u.deletedDate)}</td>
        </tr>`).join("");
}
$("delUserFilter").addEventListener("input", renderDeletedUsers);

$("printDelUsers").addEventListener("click", () => {
    if (!shownDelUsers.length) { toast("There are no deleted users to print.", true); return; }
    $("printTitle").textContent = "Deleted Users";
    $("printInfo").textContent = "Printed on " + printDate() + " | " + shownDelUsers.length + " user(s)";
    $("printHead").innerHTML = "<tr><th>No.</th><th>Full Name</th><th>Username</th><th>Role</th><th>Registered Date</th><th>Deleted Date</th></tr>";
    $("printBody").innerHTML = shownDelUsers.map((u, i) => `<tr><td>${i + 1}</td><td>${escapeHTML(u.name)}</td><td>${escapeHTML(u.username)}</td><td>${escapeHTML(roleLabels[u.role] || u.role)}</td><td>${escapeHTML(u.registered)}</td><td>${escapeHTML(u.deletedDate)}</td></tr>`).join("");
    window.print();
});

/* ===== PRINT (lists) ===== */
function printDate() {
    return new Date().toLocaleDateString("en-GB", {day:"2-digit", month:"short", year:"numeric"});
}
/* Print a LIST of files as a table (used by the "Print Results" button) */
function printFiles(list, infoText) {
    $("printTitle").textContent = "Archive Search Results";
    $("printInfo").textContent = infoText;
    $("printHead").innerHTML = "<tr><th>No.</th><th>File Name</th><th>Department</th><th>Subject No.</th><th>File No.</th><th>Archive No.</th><th>Start Date</th><th>End Date</th><th>Pages</th></tr>";
    $("printBody").innerHTML = list.map((f, i) => `<tr><td>${i + 1}</td><td>${escapeHTML(f.name)}</td><td>${escapeHTML(deptLabels[f.department] || f.department)}</td><td>${escapeHTML(f.subject)}</td><td>${escapeHTML(f.fileNo)}</td><td>${escapeHTML(f.archive)}</td><td>${escapeHTML(f.start)}</td><td>${escapeHTML(f.end)}</td><td>${escapeHTML(f.pages)}</td></tr>`).join("");
    window.print();
}
$("printAll").addEventListener("click", () => {
    if (!filteredFiles.length) { toast("There are no results to print.", true); return; }
    printFiles(filteredFiles, "Printed on " + printDate() + " | " + filteredFiles.length + " file(s)");
});
$("printDeleted").addEventListener("click", () => {
    if (!shownDeleted.length) { toast("There are no deleted files to print.", true); return; }
    $("printTitle").textContent = "Deleted Files";
    $("printInfo").textContent = "Printed on " + printDate() + " | " + shownDeleted.length + " file(s)";
    $("printHead").innerHTML = "<tr><th>No.</th><th>File Name</th><th>Department</th><th>Subject No.</th><th>Archive No.</th><th>Deleted Date</th><th>Reason</th></tr>";
    $("printBody").innerHTML = shownDeleted.map((f, i) => `<tr><td>${i + 1}</td><td>${escapeHTML(f.name)}</td><td>${escapeHTML(deptLabels[f.department] || f.department)}</td><td>${escapeHTML(f.subject)}</td><td>${escapeHTML(f.archive)}</td><td>${escapeHTML(f.deletedDate)}</td><td>${escapeHTML(f.reason)}</td></tr>`).join("");
    window.print();
});

/* ===== DOCUMENT HELPERS (used by the review dialog preview) ===== */
function fileExt(name) { return name.split(".").pop().toLowerCase(); }
function docFallback(message) {
    return `<div class="doc-fallback"><i class="fa-regular fa-file-excel"></i><p><strong>Preview not available</strong></p><p>${escapeHTML(message)}</p></div>`;
}

// Builds the preview markup for a submitted document URL (used by the review dialog)
function docHTML(url, name) {
    if (!url) return docFallback("No document is attached to this file.");
    const ext = fileExt(url), safeUrl = encodeURI(url);
    if (ext === "pdf") return `<object data="${safeUrl}" type="application/pdf">${docFallback("The PDF could not be loaded here. Use Full Screen or download it.")}</object>`;
    if (["jpg", "jpeg", "png"].includes(ext)) return `<img src="${safeUrl}" alt="${escapeHTML(name)}">`;
    return docFallback("Word and Excel files cannot be previewed in the browser. Please download the file to open it.");
}

// Pending-approval file: shows the same file record document
function viewApprovalDoc(f) {
    openViewer({...f, date: f.sentDate});
}

/* ===== FILE RECORD DOCUMENT (shown by the View button, printed by the Print buttons) ===== */
/* preview = true: on-screen viewer padding; false: print layout (A4 margins) */
function buildRecordDocument(r, preview) {
    const has = v => v !== undefined && v !== null && v !== "";
    const val = v => escapeHTML(has(v) ? v : "-");

    // Builds a numbered section with label/value pairs, two pairs per row.
    let sectionNo = 0;
    const section = (title, items) => {
        sectionNo++;
        let rows = "";
        for (let i = 0; i < items.length; i += 2) {
            const a = items[i], b = items[i + 1];
            rows += `<tr><th>${escapeHTML(a[0])}</th><td${b ? "" : ' colspan="3"'}>${val(a[1])}</td>`
                 + (b ? `<th>${escapeHTML(b[0])}</th><td>${val(b[1])}</td>` : "")
                 + `</tr>`;
        }
        return `<div class="sec"><div class="sec-title"><span class="no">${sectionNo}</span>${escapeHTML(title)}</div><table>${rows}</table></div>`;
    };

    // Key facts bar: Archive Number always, Archive Tree Number when the record has one
    const keys = [["Archive Number", r.archive]];
    if (has(r.rack)) keys.push(["Archive Tree Number", r.rack]);
    const keybar = `<div class="keybar" style="grid-template-columns:repeat(${keys.length},1fr)">`
        + keys.map(k => `<div><div class="k-lbl">${escapeHTML(k[0])}</div><div class="k-val">${val(k[1])}</div></div>`).join("")
        + `</div>`;

    const info = section("File Information", [
        ["Department", deptLabels[r.department] || r.department],
        ["Subject Number", r.subject],
        ["File Number", r.fileNo],
        ["Added Date", r.date],
        ["File Start Date", r.start],
        ["File End Date", r.end]
    ]);

    // Page details: Right/Left Side are shown only when the record has them
    const stats = [];
    if (has(r.right)) stats.push(["Right Side", r.right, false]);
    if (has(r.left)) stats.push(["Left Side", r.left, false]);
    stats.push(["Total Pages", r.pages, true]);
    sectionNo++;
    const pages = `<div class="sec">
        <div class="sec-title"><span class="no">${sectionNo}</span>Page Details</div>
        <div class="stats" style="grid-template-columns:repeat(${stats.length},1fr)">`
        + stats.map(s => `<div class="stat${s[2] ? " total" : ""}"><div class="n">${val(s[1])}</div><div class="t">${escapeHTML(s[0])}</div></div>`).join("")
        + `</div></div>`;

    return `<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8">
<base href="${escapeHTML(document.baseURI)}">
<title>Archive File - ${escapeHTML(r.archive)}</title>
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Sinhala:wght@400;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Libre+Baskerville:wght@400;700&display=swap" rel="stylesheet">
<style>
@page{size:A4;margin:0}
*{box-sizing:border-box;-webkit-print-color-adjust:exact;print-color-adjust:exact}
body{font-family:'Plus Jakarta Sans','Noto Sans Sinhala',Arial,sans-serif;color:#14233d;font-size:12.5px;line-height:1.55;margin:0;background:#fff}
${preview ? "body{padding:26px 30px}" : "body{padding:12mm}"}

/* Double-line page frame */
.sheet{border:2px solid #0b2146;padding:6px}
.page{position:relative;border:1px solid #d4a24a;padding:24px 30px 22px;overflow:hidden}
.page>*:not(.wm){position:relative;z-index:1}
.wm{position:absolute;left:50%;top:52%;width:320px;max-width:60%;transform:translate(-50%,-50%);opacity:.05;pointer-events:none}
@media print{.page{min-height:265mm;display:flex;flex-direction:column}}

/* Letterhead */
.letterhead{text-align:center}
.letterhead img{height:76px;width:auto;margin-bottom:8px}
.letterhead .si{font-family:'Noto Sans Sinhala','Plus Jakarta Sans',sans-serif;font-size:19px;font-weight:700;color:#0b2146;line-height:1.4}
.letterhead .en{font-size:10.5px;font-weight:700;letter-spacing:2px;color:#44546f;margin-top:3px}
.letterhead .sys{display:inline-block;margin-top:9px;padding:2px 16px;font-size:10.5px;font-weight:600;letter-spacing:.6px;color:#8a6420;border-top:1px solid #d4a24a;border-bottom:1px solid #d4a24a}
.divider{display:flex;align-items:center;gap:10px;margin:14px 0 16px}
.divider::before{content:"";flex:1;height:1px;background:linear-gradient(90deg,transparent,#0b2146)}
.divider::after{content:"";flex:1;height:1px;background:linear-gradient(270deg,transparent,#0b2146)}
.divider i{display:block;width:7px;height:7px;background:#d4a24a;transform:rotate(45deg)}

/* Title */
.doc-title{text-align:center;margin-bottom:14px}
h1{font-family:'Libre Baskerville',Georgia,serif;font-size:18px;font-weight:700;letter-spacing:2.5px;text-transform:uppercase;color:#0b2146;margin:0}
.subtitle{font-size:11px;color:#6a7890;margin-top:3px}

/* Key facts bar */
.keybar{display:grid;margin-bottom:14px;border:1px solid #cdd6e6;border-top:3px solid #0b2146;background:#f8f9fc}
.keybar>div{padding:9px 14px;text-align:center}
.keybar>div+div{border-left:1px solid #cdd6e6}
.k-lbl{font-size:9.5px;font-weight:700;letter-spacing:1.2px;text-transform:uppercase;color:#6a7890}
.k-val{margin-top:2px;font-size:16px;font-weight:700;color:#16386a;overflow-wrap:anywhere}

/* File name */
.name-box{margin-bottom:16px;padding:6px 2px 10px;border-bottom:1px solid #cdd6e6}
.name-box .lbl{font-size:9.5px;font-weight:700;letter-spacing:1.2px;text-transform:uppercase;color:#6a7890}
.name-box .nm{font-size:16px;font-weight:700;color:#0b2146;overflow-wrap:anywhere}

/* Sections */
.sec{margin-bottom:16px;break-inside:avoid;page-break-inside:avoid}
.sec-title{display:flex;align-items:center;gap:10px;margin-bottom:2px;padding-bottom:5px;border-bottom:2px solid #d4a24a;font-size:11px;font-weight:700;letter-spacing:1.3px;text-transform:uppercase;color:#0b2146}
.sec-title .no{display:inline-grid;place-items:center;width:20px;height:20px;border-radius:50%;background:#0b2146;color:#fff;font-size:10px;letter-spacing:0}
table{width:100%;border-collapse:collapse;table-layout:fixed}
th,td{padding:8px 10px;text-align:left;vertical-align:top;border-bottom:1px solid #e3e8f2;overflow-wrap:anywhere}
th{width:19%;font-size:11px;font-weight:600;color:#6a7890}
td{width:31%;font-weight:600;color:#14233d}
td[colspan="3"]{width:81%}

/* Page statistics */
.stats{display:grid;gap:10px;margin-top:10px}
.stat{padding:9px 10px;text-align:center;border:1px solid #cdd6e6;background:#fff}
.stat .n{font-size:20px;font-weight:700;line-height:1.2;color:#16386a}
.stat .t{font-size:9.5px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:#6a7890}
.stat.total{background:#0b2146;border-color:#0b2146}
.stat.total .n{color:#fff}
.stat.total .t{color:#f0cf8a}
</style></head>
<body>
<div class="sheet"><div class="page">
    <img class="wm" src="logo.webp" alt="" onerror="this.style.display='none'">

    <div class="letterhead">
        <img src="logo.webp" alt="" onerror="this.style.display='none'">
        <div class="si">දකුණු පළාත් සභා ලේකම් කාර්යාලය</div>
        <div class="en">SOUTHERN PROVINCIAL COUNCIL SECRETARIAT</div>
        <div class="sys">Archives File Management System</div>
    </div>
    <div class="divider"><i></i></div>

    <div class="doc-title">
        <h1>Archive File Record</h1>
        <div class="subtitle">Official record of an archived file</div>
    </div>

    ${keybar}

    <div class="name-box">
        <div class="lbl">File Name</div>
        <div class="nm">${val(r.name)}</div>
    </div>

    ${info}
    ${pages}
</div></div>
</body></html>`;
}

/* ===== PRINT the file record document (same layout as the View button) =====
   Used by BOTH the row Print button and the Print button inside the viewer. */
function printRecordDocument(file) {
    if (!file) return;

    const old = document.getElementById("printFrame");
    if (old) old.remove();

    const frame = document.createElement("iframe");
    frame.id = "printFrame";
    frame.setAttribute("aria-hidden", "true");
    frame.style.cssText = "position:fixed;right:0;bottom:0;width:0;height:0;border:0;visibility:hidden";
    frame.onload = function() {
        setTimeout(function() {
            try {
                frame.contentWindow.focus();
                frame.contentWindow.print();
            } catch (err) {
                toast("Could not open the print dialog: " + err.message, true);
            }
        }, 400);
    };
    frame.srcdoc = buildRecordDocument(file, false);
    document.body.appendChild(frame);
}

/* ===== VIEW BUTTON: shows the file record ===== */
const docFrame = $("docFrame");
let viewedFile = null;

function openViewer(file) {
    viewedFile = file;
    $("docSub").textContent = (file.archive || "") + " | " + (file.name || "");
    docFrame.srcdoc = buildRecordDocument(file, true);
    openModal("docViewer");
}

/* Print the record that is open in the viewer */
function printViewedFile() {
    printRecordDocument(viewedFile);
}
$("docPrintBtn").addEventListener("click", printViewedFile);

/* ===== MODALS ===== */
function openModal(id) { $(id).hidden = false; document.body.style.overflow = "hidden"; }
function closeModal(id) {
    $(id).hidden = true;
    if (id === "docViewer") { docFrame.srcdoc = ""; viewedFile = null; }
    if (id === "approvalModal") $("apPreview").innerHTML = "";
    if (document.querySelectorAll(".modal:not([hidden])").length === 0) document.body.style.overflow = "";
}
document.querySelectorAll("[data-close]").forEach(b => b.addEventListener("click", () => closeModal(b.dataset.close)));
document.querySelectorAll(".modal").forEach(m => m.addEventListener("click", e => { if (e.target === m) closeModal(m.id); }));

/* ===== SIDEBAR + PROFILE ===== */
const app = $("app"), sidebar = $("sidebar"), menuToggle = $("menuToggle"), sidebarOverlay = $("sidebarOverlay");

function toggleSidebar() {
    if (window.innerWidth <= 700) {
        const isOpen = sidebar.classList.toggle("open");
        sidebarOverlay.classList.toggle("show", isOpen);
        document.body.style.overflow = isOpen ? "hidden" : "";
        menuToggle.setAttribute("aria-expanded", String(isOpen));
    } else {
        const isCollapsed = app.classList.toggle("sidebar-collapsed");
        menuToggle.setAttribute("aria-expanded", String(!isCollapsed));
    }
}
function closeSidebar() {
    sidebar.classList.remove("open");
    sidebarOverlay.classList.remove("show");
    if (document.querySelectorAll(".modal:not([hidden])").length === 0) document.body.style.overflow = "";
    if (window.innerWidth <= 700) menuToggle.setAttribute("aria-expanded", "false");
}
menuToggle.addEventListener("click", toggleSidebar);
sidebarOverlay.addEventListener("click", closeSidebar);

const profileToggle = $("profileToggle"), profilePopover = $("profilePopover");
function closeProfilePopover() { profilePopover.hidden = true; profileToggle.setAttribute("aria-expanded", "false"); }
profileToggle.addEventListener("click", e => {
    e.stopPropagation();
    profilePopover.hidden = !profilePopover.hidden;
    profileToggle.setAttribute("aria-expanded", String(!profilePopover.hidden));
});
profilePopover.addEventListener("click", e => e.stopPropagation());
document.addEventListener("click", closeProfilePopover);
$("profilePasswordLink").addEventListener("click", function(e) {
    e.preventDefault();
    closeProfilePopover();
    showSection("password");
});

/* ===== CHANGE PASSWORD ===== */
function showPasswordAlert(message, isError) {
    const box = $("passwordAlert");
    box.className = "alert " + (isError ? "alert-error" : "alert-success");
    box.querySelector("i").className = "fa-solid " + (isError ? "fa-circle-exclamation" : "fa-circle-check");
    $("passwordAlertText").textContent = message;
    box.hidden = false;
    box.scrollIntoView({block: "nearest", behavior: "smooth"});
}

$("passwordForm").addEventListener("submit", async function(e) {
    e.preventDefault();
    const button = $("passwordSave");
    const current = $("currentPassword").value;
    const newPass = $("newPassword").value;
    const confirmPass = $("confirmPassword").value;

    if (newPass.length < 6) { showPasswordAlert("New password must contain at least 6 characters.", true); return; }
    if (newPass !== confirmPass) { showPasswordAlert("New Password and Confirm Password do not match.", true); return; }

    button.disabled = true;
    try {
        await api("change_password", {current: current, new: newPass, confirm: confirmPass});
        this.reset();
        showPasswordAlert("Your password was updated successfully. Use the new password the next time you log in.", false);
    } catch (ex) {
        showPasswordAlert(ex.message, true);
    } finally {
        button.disabled = false;
    }
});

document.addEventListener("keydown", e => {
    if (e.key === "Escape") {
        document.querySelectorAll(".modal:not([hidden])").forEach(m => closeModal(m.id));
        closeSidebar();
        closeProfilePopover();
    }
});
window.addEventListener("resize", () => {
    if (window.innerWidth > 700) closeSidebar(); else app.classList.remove("sidebar-collapsed");
});

/* ===== INITIAL DISPLAY + AUTO SYNC ===== */
function renderAll() {
    runSearch();          // also refreshes the stat cards
    renderApprovals();
    renderRegistrations();
    renderDeleted();
    renderUsers();
    renderDeletedUsers();
}

function refresh() { loadState().catch(ex => { if (!ex.logout) toast(ex.message, true); }); }
refresh();

// Refresh from the database every 5 seconds and when this tab is shown again
setInterval(() => { if (!document.hidden) loadState().catch(() => {}); }, 5000);
document.addEventListener("visibilitychange", () => { if (!document.hidden) loadState().catch(() => {}); });
</script>

</body>
</html>