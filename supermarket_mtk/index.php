<?php
require __DIR__ . '/config.php';
try {
    $produk = ambil_produk();
} catch (Throwable $e) {
    halaman_error($e);
}
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Pelangi Mart</title>
</head>
<body>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;600;700;800&family=DM+Mono:wght@400;500&family=Nunito:wght@600;700;800&display=swap" rel="stylesheet" media="print" onload="this.media='all'">
<noscript><link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;600;700;800&family=DM+Mono:wght@400;500&family=Nunito:wght@600;700;800&display=swap" rel="stylesheet"></noscript>
<style>
/* Layout: layar judul terbelah (teks + toko ilustrasi); di dalam game ada menu atas, lalu halaman penuh. Rak belanja berdampingan dengan keranjang lengket. */
:root{
  --bg:#FFF5FA; --ink:#46356E; --ink-soft:#6F5F95; --card:#FFFFFF; --line:#EFE0F7;
  --pink:#FF9EC7; --pink-deep:#E26BA2; --pink-soft:#FFDCEB;
  --mint:#8FE3C8; --mint-deep:#4DBF9C; --mint-soft:#D9F8EC;
  --sky:#8CCBFF; --sky-deep:#4FA3E8; --sky-soft:#DCEFFF;
  --lav:#B9A6FF; --lav-deep:#8E78E8; --lav-soft:#EAE3FF;
  --butter:#FFDF7A; --butter-deep:#E8B93A; --butter-soft:#FFF3C6;
  --peach:#FFB48A; --peach-deep:#E88A5A; --peach-soft:#FFE4D4;
  --wood:#F3C9A5; --wood-deep:#DDA67E;
  --good:#1F7A5A; --bad:#C23A63;
  --f-display:'Baloo 2','Trebuchet MS',system-ui,sans-serif;
  --f-body:'Nunito','Segoe UI',system-ui,sans-serif;
  --f-mono:'DM Mono',ui-monospace,Menlo,Consolas,monospace;
  --radius:22px;
  color-scheme:light;
}
html{font-size:clamp(16px,.35vw + 12px,19px)}
html,body{height:100%}
body{margin:0;background:var(--bg);color:var(--ink);font-family:var(--f-body);font-size:1rem;font-weight:600;line-height:1.5}
*,*::before,*::after{box-sizing:border-box}
#app{position:relative;isolation:isolate;min-height:100%}
#app::before{content:"";position:fixed;inset:0;z-index:-1;pointer-events:none;
  background:
    radial-gradient(circle at 8% 12%,var(--pink-soft) 0 18%,transparent 40%),
    radial-gradient(circle at 92% 20%,var(--sky-soft) 0 16%,transparent 38%),
    radial-gradient(circle at 80% 95%,var(--mint-soft) 0 18%,transparent 42%),
    radial-gradient(circle at 12% 90%,var(--butter-soft) 0 14%,transparent 36%);}
button,input,select{font-family:inherit;color:inherit}
h1,h2,h3{font-family:var(--f-display);font-weight:800;line-height:1.1;margin:0;text-wrap:balance}
p{margin:0}
.ptitle{font-size:clamp(2rem,4vw,2.8rem);margin-bottom:.4rem}
.sub{color:var(--ink-soft);max-width:60ch}

/* tombol */
.btn{--sh:var(--pink-deep);font-family:var(--f-display);font-weight:700;font-size:1.08rem;line-height:1.1;border:0;cursor:pointer;color:var(--ink);background:var(--pink);
  padding:.8rem 1.4rem;border-radius:999px;box-shadow:0 5px 0 var(--sh);display:inline-flex;align-items:center;justify-content:center;gap:.5rem;transition:transform .08s,box-shadow .08s}
.btn:hover{transform:translateY(-1px)}
.btn:active{transform:translateY(4px);box-shadow:0 1px 0 var(--sh)}
.btn:focus-visible,.navlink:focus-visible,.eb:focus-visible,.bitem:focus-visible,.brand:focus-visible{outline:3px solid var(--ink);outline-offset:3px}
.btn.mint{background:var(--mint);--sh:var(--mint-deep)}
.btn.sky{background:var(--sky);--sh:var(--sky-deep)}
.btn.butter{background:var(--butter);--sh:var(--butter-deep)}
.btn.ghost{background:var(--card);--sh:var(--line);border:2px solid var(--line)}
.btn.sm{font-size:.92rem;padding:.5rem .95rem;box-shadow:0 3px 0 var(--sh)}
.btn.sm:active{box-shadow:0 1px 0 var(--sh);transform:translateY(2px)}
.btn[disabled]{opacity:.5;cursor:not-allowed}

/* layar judul */
.title{display:grid;grid-template-columns:1.05fr 1fr;align-items:center;gap:3rem;min-height:100%;width:100%;max-width:none;margin-inline:auto;padding-block:2.5rem;padding-inline:clamp(24px,6vw,140px)}
.eyebrow{display:inline-block;font-family:var(--f-display);font-weight:700;background:var(--butter);padding:.25rem .9rem;border-radius:999px;margin-bottom:1rem}
.wordmark{font-size:clamp(3.4rem,9vw,6.6rem);line-height:.88;letter-spacing:-.01em}
.wordmark span{display:block}
.wordmark .mart{display:inline-block;background:var(--pink);padding:.02em .22em .08em;border-radius:.28em;transform:rotate(-2deg);margin-top:.08em}
.rainbow{width:min(280px,70%);height:14px;border-radius:999px;margin:1.4rem 0 1.2rem;background:linear-gradient(90deg,var(--pink) 0 25%,var(--butter) 25% 50%,var(--mint) 50% 75%,var(--sky) 75% 100%)}
.lead{font-size:1.2rem;max-width:34ch;color:var(--ink-soft)}
.play{font-size:1.9rem;padding:1rem 2.8rem 1.05rem;margin-top:1.8rem;--sh:var(--pink-deep);box-shadow:0 8px 0 var(--sh)}
.play:active{box-shadow:0 2px 0 var(--sh);transform:translateY(6px)}
.play svg{width:1.4rem;height:1.4rem}
.fine{margin-top:1.4rem;font-size:.95rem;color:var(--ink-soft)}
.title-art{position:relative;max-width:min(780px,100%);width:100%;justify-self:center}
.shop-svg{width:100%;height:auto;display:block}
.f-white{fill:#fff}.f-pink{fill:var(--pink)}.f-butter{fill:var(--butter)}.f-mint{fill:var(--mint)}.f-sky{fill:var(--sky-soft)}.f-shadow{fill:var(--lav-soft)}.f-lav{fill:var(--lav)}
.s-ink{stroke:var(--ink);stroke-width:3;stroke-linejoin:round}
.svg-sign{font-family:var(--f-display);font-weight:800;font-size:27px;fill:var(--ink)}
.svg-small{font-family:var(--f-display);font-weight:800;font-size:12px;fill:var(--ink)}
.svg-emo{font-size:26px}
.float{position:absolute;font-size:2.6rem;line-height:1;filter:drop-shadow(0 4px 0 rgba(70,53,110,.12))}
@media (prefers-reduced-motion:no-preference){
  .float{animation:floaty 4s ease-in-out infinite;animation-delay:var(--d,0s)}
  .play{animation:bob 1.9s ease-in-out infinite}
  .play:hover{animation:none}
}
@keyframes floaty{50%{transform:translateY(-12px) rotate(6deg)}}
@keyframes bob{50%{transform:translateY(-6px)}}

/* menu atas */
.nav{position:sticky;top:env(safe-area-inset-top,0px);z-index:20;display:flex;flex-wrap:wrap;align-items:center;gap:.6rem 1rem;padding:.7rem clamp(20px,4vw,72px);background:rgba(255,245,250,.92);backdrop-filter:blur(8px);border-bottom:2px solid var(--line)}
.brand{display:inline-flex;align-items:center;gap:.5rem;background:none;border:0;cursor:pointer;font-family:var(--f-display);font-weight:800;font-size:1.4rem;color:var(--ink);padding:.2rem;border-radius:12px}
.brand svg{width:40px;height:30px}
.navlinks{display:flex;flex-wrap:wrap;gap:.2rem}
.navlink{font-family:var(--f-display);font-weight:700;font-size:1.05rem;background:transparent;border:0;color:var(--ink);padding:.45rem 1rem;border-radius:999px;cursor:pointer;display:inline-flex;gap:.45rem;align-items:center}
.navlink:hover{background:var(--pink-soft)}
.navlink.on{background:var(--lav-soft);box-shadow:inset 0 0 0 2px var(--lav)}
.badge{background:var(--ink);color:#fff;border-radius:999px;font-size:.8rem;min-width:1.4rem;height:1.4rem;padding:0 .4rem;display:inline-grid;place-items:center}
.nav-right{margin-left:auto;display:flex;gap:.5rem;flex-wrap:wrap}

.page{width:100%;max-width:none;margin-inline:auto;padding:1.8rem clamp(20px,4vw,72px) 4rem}
.card{background:var(--card);border:2px solid var(--line);border-radius:var(--radius);padding:1.2rem 1.4rem}
.card h2{font-size:1.35rem;margin-bottom:.6rem}

/* dasbor */
.dash{display:grid;gap:1.2rem;margin-top:1.4rem}
.hero{display:flex;align-items:center;gap:1.4rem;background:var(--pink-soft);border-radius:28px;padding:1.4rem 1.6rem;border:2px solid var(--pink)}
.mascot{flex:none;width:92px;height:92px;border-radius:50%;background:#fff;display:grid;place-items:center;font-size:3.4rem;box-shadow:0 5px 0 var(--pink);overflow:hidden}
.mascot img{width:100%;height:100%;object-fit:cover;display:block}
.hero h2{font-size:1.9rem}
.hero .lv{font-family:var(--f-display);font-weight:700;font-size:1.15rem}
.meter{height:16px;background:#fff;border-radius:999px;overflow:hidden;margin:.7rem 0 .3rem;border:2px solid #fff;max-width:520px}
.meter i{display:block;height:100%;border-radius:999px;background:var(--pink-deep);transition:width .4s}
.hero-body{min-width:0;flex:1}
.stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:1rem}
.stat{border-radius:22px;padding:1rem 1.1rem;display:grid;gap:.1rem}
.stat b{font-family:var(--f-display);font-weight:800;font-size:2.3rem;line-height:1;font-variant-numeric:tabular-nums}
.stat span{font-weight:800;font-size:.95rem}
.stat.a{background:var(--butter-soft)}.stat.b{background:var(--mint-soft)}.stat.c{background:var(--sky-soft)}.stat.d{background:var(--lav-soft)}
.dash-grid{display:grid;grid-template-columns:1.2fr 1fr;gap:1.2rem;align-items:start}
.acts{display:grid;gap:.8rem}
.act{display:flex;align-items:center;gap:1rem;text-align:left;background:var(--card);border:2px solid var(--line);border-radius:20px;padding:.9rem 1.1rem;cursor:pointer;font-family:var(--f-body);transition:transform .1s,border-color .1s}
.act:hover{transform:translateY(-2px);border-color:var(--pink)}
.act:focus-visible{outline:3px solid var(--ink);outline-offset:3px}
.act .pic{flex:none;font-size:2rem;width:56px;height:56px;border-radius:16px;display:grid;place-items:center}
.act b{display:block;font-family:var(--f-display);font-size:1.2rem;line-height:1.2}
.act small{color:var(--ink-soft);font-weight:700;font-size:.92rem}
.tip{background:var(--butter-soft);border-color:var(--butter)}
.tip p{margin-bottom:.9rem}
.tip .tl{font-family:var(--f-display);font-weight:700}
.chips{display:flex;flex-wrap:wrap;gap:.4rem;margin-top:.4rem}
.chip{background:var(--lav-soft);border-radius:999px;padding:.2rem .8rem;font-weight:800;font-size:.9rem}

/* rak belanja */
.shop{display:grid;grid-template-columns:minmax(0,1fr) 400px;gap:1.5rem;align-items:start;margin-top:1.2rem}
.shelves{display:grid;gap:1rem}
.shelf{background:var(--back);border:2px solid var(--line);border-radius:26px;padding:0 1rem 1rem;margin-top:.9rem}
.shelf-sign{display:inline-block;margin:-.9rem 0 .5rem .2rem;background:var(--sign);padding:.25rem 1.1rem;border-radius:12px;font-family:var(--f-display);font-weight:700;font-size:1.1rem;box-shadow:0 3px 0 rgba(70,53,110,.15)}
.shelf-row{display:flex;flex-wrap:wrap;align-items:flex-start}
.prod{appearance:none;background:none;border:0;padding:0;cursor:pointer;position:relative;width:7.8rem;display:flex;flex-direction:column;align-items:center;margin-top:.6rem}
.prod-pic{font-size:3.4rem;line-height:1;padding-bottom:.25rem;transition:transform .15s;filter:drop-shadow(0 4px 0 rgba(70,53,110,.14))}
.prod:hover .prod-pic{transform:translateY(-8px) scale(1.07)}
.prod:focus-visible{outline:3px solid var(--ink);outline-offset:2px;border-radius:12px}
.plank{display:block;width:100%;height:16px;background:var(--wood);border-bottom:5px solid var(--wood-deep)}
.tag{display:flex;flex-direction:column;align-items:center;justify-content:flex-start;gap:.15rem;min-height:4.6rem;background:#fff;border-radius:0 0 12px 12px;padding:.35rem .35rem .45rem;width:calc(100% - 14px);box-shadow:0 2px 0 var(--line);text-align:center;line-height:1.15}
.tag b{font-size:.84rem;font-weight:800;overflow-wrap:anywhere}
.tag i{font-style:normal;font-family:var(--f-display);font-weight:700;font-size:.95rem;background:var(--butter-soft);border-radius:999px;padding:0 .6rem;font-variant-numeric:tabular-nums}
.qty{position:absolute;top:-2px;right:14px;background:var(--mint);border-radius:999px;font-family:var(--f-display);font-weight:800;font-size:.95rem;padding:.02rem .6rem;box-shadow:0 2px 0 var(--mint-deep)}
.pop{animation:pop .3s}
@keyframes pop{40%{transform:translateY(-16px) scale(1.18)}}
.cart{position:sticky;top:5.6rem;background:var(--card);border:2px solid var(--line);border-radius:var(--radius);padding:1.1rem 1.2rem;display:grid;gap:.8rem}
.cart h2{font-size:1.4rem}
.clist{list-style:none;margin:0;padding:0;display:grid;gap:.5rem;max-height:calc(100vh - 24rem);min-height:60px;overflow:auto}
.cline{display:grid;grid-template-columns:auto minmax(0,1fr) auto auto;gap:.6rem;align-items:center;background:var(--bg);border-radius:14px;padding:.45rem .6rem}
.ce{font-size:1.7rem;line-height:1}
.cn{min-width:0;line-height:1.2}
.cn b{display:block;font-size:.92rem;overflow-wrap:anywhere}
.cn small{color:var(--ink-soft);font-size:.8rem;font-weight:700}
.stepq{display:flex;align-items:center;gap:.3rem}
.stepq button{width:28px;height:28px;border-radius:50%;border:2px solid var(--line);background:#fff;cursor:pointer;font-weight:800;font-size:1rem;line-height:1;display:grid;place-items:center;padding:0}
.stepq button:hover{border-color:var(--pink-deep)}
.stepq output{min-width:1.2rem;text-align:center;font-weight:800;font-variant-numeric:tabular-nums}
.xbtn{border:0;background:transparent;font-size:1.3rem;cursor:pointer;color:var(--ink-soft);padding:.1rem .4rem;border-radius:8px}
.xbtn:hover{color:var(--bad)}
.note-soft{font-size:.88rem;color:var(--ink-soft);background:var(--lav-soft);border-radius:12px;padding:.5rem .75rem}
.cart-actions{display:grid;gap:.6rem}
.empty-box{text-align:center;color:var(--ink-soft);padding:1rem .5rem}

/* kasir */
.steps{display:flex;gap:.6rem;flex-wrap:wrap;margin:.8rem 0 1.2rem}
.step{display:flex;align-items:center;gap:.5rem;padding:.3rem 1rem .3rem .35rem;border-radius:999px;background:#fff;border:2px solid var(--line);font-weight:800}
.step b{width:28px;height:28px;border-radius:50%;display:grid;place-items:center;background:var(--line);font-family:var(--f-display)}
.step.on{border-color:var(--pink-deep);background:var(--pink-soft)}
.step.on b{background:var(--pink)}
.step.past b{background:var(--mint)}
.kasir-grid{display:grid;grid-template-columns:minmax(0,1.1fr) minmax(0,1fr);gap:1.4rem;align-items:start}
.belt-card{background:var(--sky-soft);border-color:var(--sky)}
.belt{display:flex;flex-wrap:wrap;gap:.6rem;align-content:flex-start;min-height:150px;max-height:260px;overflow:auto;padding:.4rem .2rem .8rem}
.belt-empty{align-self:center;width:100%;text-align:center;color:var(--ink-soft)}
.track{height:24px;border-radius:12px;background:repeating-linear-gradient(90deg,var(--lav) 0 18px,var(--lav-deep) 18px 22px);box-shadow:0 4px 0 var(--lav-deep)}
.scanner{display:grid;grid-template-columns:96px minmax(0,1fr);gap:1rem;align-items:center;margin-top:1.1rem}
.laser{position:relative;height:60px;border-radius:14px;background:var(--ink);overflow:hidden}
.laser::after{content:"";position:absolute;left:8px;right:8px;top:20%;height:3px;border-radius:3px;background:#FF7BAA;box-shadow:0 0 12px 2px #FF7BAA}
.laser.flash{background:#6B4FA8}
@media (prefers-reduced-motion:no-preference){
  .track{animation:belt 1s linear infinite}
  .laser::after{animation:sweep 1.4s ease-in-out infinite alternate}
}
@keyframes belt{to{background-position:22px 0}}
@keyframes sweep{to{top:75%}}
.counter{font-weight:800;font-size:.95rem}
.bar{height:10px;background:#fff;border-radius:999px;margin-top:.4rem;overflow:hidden}
.bar i{display:block;height:100%;background:var(--mint-deep);border-radius:999px;transition:width .25s}
.bitem{appearance:none;border:0;background:#fff;width:66px;height:66px;border-radius:18px;font-size:2.1rem;line-height:1;cursor:pointer;box-shadow:0 4px 0 rgba(70,53,110,.16);transition:transform .1s;display:grid;place-items:center;padding:0}
.bitem:hover{transform:translateY(-4px)}
.zap{animation:zap .26s forwards;pointer-events:none}
@keyframes zap{to{transform:translateY(46px) scale(.4);opacity:0}}
.monitor{background:var(--lav);border-radius:26px;padding:14px 14px 18px;box-shadow:0 8px 0 var(--lav-deep)}
.screen{background:#2A2150;color:#C9FFE8;border-radius:16px;padding:.9rem 1rem;font-family:var(--f-mono);font-size:.95rem}
.scr-head,.scr-foot{display:flex;justify-content:space-between;gap:1rem;font-weight:500;letter-spacing:.06em;font-size:.82rem}
.scr-head{padding-bottom:.5rem;border-bottom:2px solid rgba(201,255,232,.35)}
.scr-foot{padding-top:.6rem;border-top:2px solid rgba(201,255,232,.35)}
.scr-lines{list-style:none;margin:0;padding:0;min-height:210px;max-height:300px;overflow:auto}
.scr-lines li{display:grid;grid-template-columns:2.2ch minmax(0,1fr) auto;gap:.7rem;padding:.28rem 0;border-bottom:1px dashed rgba(201,255,232,.22);font-variant-numeric:tabular-nums}
.scr-lines li .nm{overflow-wrap:anywhere}
.scr-hint{color:rgba(201,255,232,.65);padding:.6rem 0}
@media (prefers-reduced-motion:no-preference){.scr-lines li.new{animation:lineIn .25s}}
@keyframes lineIn{from{opacity:0;transform:translateX(-10px)}}

/* adegan kasir */
.kasir-scene{display:grid;grid-template-columns:minmax(0,1.3fr) minmax(0,1fr);gap:1.4rem;align-items:end;background:linear-gradient(180deg,var(--sky-soft) 0 80%,#FFE9D6 80% 100%);border:2px solid var(--sky);border-radius:var(--radius);padding:1.2rem 1.4rem 0;overflow:hidden}
.k-left{display:flex;flex-direction:column;min-width:0}
.k-cust{display:flex;flex-direction:column;align-items:center;align-self:flex-start;margin-left:.6rem;position:relative;z-index:1}
.k-cust.leave{will-change:transform;backface-visibility:hidden;animation:walkOut 1.3s cubic-bezier(.45,.05,.55,.95) forwards}
.k-cust.leave .k-face{will-change:transform;animation:hop .32s ease-in-out infinite alternate}
.k-cust.gone{visibility:hidden}
@keyframes walkOut{from{transform:translate3d(0,0,0)}to{transform:translate3d(var(--lx,-170%),0,0)}}
@keyframes hop{to{transform:translateY(-10px)}}
.k-bubble{max-width:9.5rem;text-align:center;background:#fff;border-radius:18px;padding:.5rem 1rem;font-weight:800;box-shadow:0 3px 0 rgba(70,53,110,.12);position:relative;margin-bottom:.9rem}
.k-bubble::after{content:"";position:absolute;left:50%;bottom:-8px;width:16px;height:16px;background:#fff;transform:translateX(-50%) rotate(45deg)}
.k-face{font-size:6rem;line-height:1;filter:drop-shadow(0 6px 0 rgba(70,53,110,.14))}
.k-name{font-weight:800;font-size:.85rem;color:var(--ink-soft);margin-top:.2rem}
.konter{position:relative;z-index:2}
.konter-top{display:flex;align-items:flex-end;gap:.6rem;background:#E9E4F5;border-radius:16px 16px 0 0;padding:.6rem .8rem 0;border:2px solid #D3CBE8;border-bottom:0}
.belt-wrap{flex:1;min-width:0;display:flex;flex-direction:column}
.konter .belt{min-height:84px;max-height:170px;justify-content:flex-start;align-content:flex-end;padding:.2rem .4rem 0;gap:.4rem;overflow:auto;position:relative;z-index:1;margin-bottom:-12px}
.konter .belt-empty{margin:0 0 1.6rem;align-self:flex-end;font-weight:700}
.konter .bitem{width:56px;height:56px;font-size:1.8rem}
.konter .track{height:26px;border-radius:6px;background:repeating-linear-gradient(90deg,#5A5470 0 18px,#463F5C 18px 22px);box-shadow:inset 0 3px 0 rgba(0,0,0,.25)}
.scan-unit{flex:none;width:104px;display:flex;flex-direction:column;align-items:center;gap:.25rem;background:#fff;border-radius:12px 12px 0 0;padding:.5rem .5rem .4rem;box-shadow:0 -2px 0 #D3CBE8}
.scan-unit .laser{width:100%;height:46px}
.scan-label{font-family:var(--f-mono);font-size:.68rem;font-weight:700;letter-spacing:.1em;color:var(--ink-soft)}
.konter-body{display:flex;align-items:center;justify-content:space-between;gap:1rem;background:var(--lav);border:2px solid var(--lav-deep);border-bottom:0;padding:1rem 1.1rem 1.1rem;min-height:110px}
.konter-body .counter{flex:1;min-width:0;background:#fff;border-radius:12px;padding:.6rem .8rem}
.konter-sign{flex:none;display:flex;flex-direction:column;align-items:center;gap:.35rem;background:var(--ink);color:#C9FFE8;border-radius:14px;padding:.6rem .8rem;max-width:9.5rem}
.ks-photo{width:58px;height:58px;border-radius:50%;overflow:hidden;background:#fff;border:3px solid #C9FFE8;display:grid;place-items:center;font-size:2rem;line-height:1}
.ks-photo img{width:100%;height:100%;object-fit:cover;display:block}
.ks-name{font-family:var(--f-mono);font-weight:700;letter-spacing:.05em;font-size:.8rem;max-width:8.2rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.k-right{display:flex;flex-direction:column;min-width:0;align-self:stretch;justify-content:flex-end;padding-bottom:1.2rem}
.duo{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:1rem;align-items:stretch}
.duo .card{display:flex;flex-direction:column;align-items:flex-start;gap:.4rem}
.duo .sw{margin-top:auto}
@media (max-width:900px){.duo{grid-template-columns:minmax(0,1fr)}}
/* beranda */
.d-head{display:flex;justify-content:space-between;align-items:flex-end;gap:1rem;flex-wrap:wrap}
.d-quick{display:flex;gap:.6rem;flex-wrap:wrap}
.d-kpis{display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:.9rem;margin:1.2rem 0}
.d-kpi{display:flex;align-items:center;gap:.8rem;background:#fff;border:2px solid var(--line);border-radius:20px;padding:.9rem 1rem}
.d-ic{flex:none;width:48px;height:48px;border-radius:14px;display:grid;place-items:center;font-size:1.6rem}
.d-kpi b{display:block;font-family:var(--f-display);font-size:1.45rem;line-height:1.15;overflow-wrap:anywhere}
.d-kpi span:not(.d-ic){font-weight:700;font-size:.85rem;color:var(--ink-soft)}
.d-grid{display:grid;gap:1rem;margin-top:1rem;align-items:stretch}
.d-g1{grid-template-columns:minmax(0,1.7fr) minmax(0,1fr)}
.d-g2{grid-template-columns:repeat(3,minmax(0,1fr))}
.d-g3{grid-template-columns:minmax(0,1.7fr) minmax(0,1fr)}
.d-grid .card h2{margin-bottom:.7rem}
.d-ct{display:flex;justify-content:space-between;align-items:baseline;gap:1rem}
.d-sum{font-weight:800;color:var(--ink-soft)}
.d-empty{color:var(--ink-soft);background:var(--bg);border-radius:14px;padding:1.2rem;text-align:center;font-weight:700}
.d-bars{display:grid;grid-template-columns:repeat(7,minmax(0,1fr));gap:.6rem;height:230px;align-items:end}
.d-col{display:flex;flex-direction:column;align-items:center;height:100%;gap:.3rem;min-width:0}
.d-val{font-size:.78rem;font-weight:800;color:var(--ink-soft);min-height:1rem;white-space:nowrap}
.d-barwrap{flex:1;width:100%;display:flex;align-items:flex-end;background:var(--bg);border-radius:12px;overflow:hidden}
.d-barwrap i{display:block;width:100%;background:linear-gradient(180deg,var(--pink),var(--pink-deep));border-radius:12px 12px 0 0;transition:height .4s}
.d-lab{font-size:.8rem;font-weight:800;color:var(--ink-soft)}
.d-donutrow{display:flex;align-items:center;gap:1.4rem;flex-wrap:wrap;justify-content:center;padding:.4rem 0}
.d-donut{width:150px;height:150px;border-radius:50%;display:grid;place-items:center}
.d-donut>div{width:98px;height:98px;border-radius:50%;background:#fff;display:grid;place-items:center}
.d-donut b{font-family:var(--f-display);font-size:1.7rem}
.d-legend{list-style:none;margin:0;padding:0;display:grid;gap:.5rem;font-weight:700}
.d-legend i{display:inline-block;width:12px;height:12px;border-radius:50%;margin-right:.5rem}
.d-hbars,.d-top{list-style:none;margin:0;padding:0;display:grid;gap:.8rem}
.d-hl{display:flex;justify-content:space-between;gap:.6rem;font-weight:700;font-size:.92rem;margin-bottom:.25rem}
.d-ht{height:10px;background:var(--bg);border-radius:999px;overflow:hidden}
.d-ht i{display:block;height:100%;border-radius:999px}
.d-top li{display:flex;gap:.8rem;align-items:center}
.d-top li>div{flex:1;min-width:0}
.d-top b{display:block}
.d-top small{display:block;color:var(--ink-soft);font-weight:700;margin-bottom:.25rem}
.d-e{flex:none;font-size:1.9rem;line-height:1}
.d-stack{display:grid;gap:1rem;align-content:stretch}
.d-level{display:flex;gap:1rem;align-items:center}
.d-level>div:last-child{flex:1;min-width:0}
.d-level h2{margin:0 0 .4rem!important}
.d-tbl td,.d-tbl th{white-space:nowrap}
.d-g3 .tip{display:flex;flex-direction:column;align-items:flex-start;gap:.6rem}
@media (max-width:900px){.d-g1,.d-g2,.d-g3{grid-template-columns:minmax(0,1fr)}.d-bars{height:190px}}
/* profil */
.pf-grid{display:grid;grid-template-columns:minmax(0,1.6fr) minmax(0,1fr);gap:1.2rem;margin-top:1.2rem;align-items:start}
.pf-fields{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:1rem}
textarea.in{resize:vertical;font-family:inherit}
.pf-card{text-align:center;display:grid;justify-items:center;gap:.4rem;position:sticky;top:5.6rem}
.pf-card .mascot{width:110px;height:110px;font-size:4rem}
.pf-photo-row{display:flex;align-items:center;gap:1.2rem;flex-wrap:wrap}
.pf-photo-btns{display:flex;gap:.6rem;flex-wrap:wrap;align-items:center;max-width:22rem}
.pf-photo-btns small{flex-basis:100%;color:var(--ink-soft);font-weight:700}
.pf-file{cursor:pointer}
.pf-file:focus-within{outline:3px solid var(--ink);outline-offset:3px}
.d-acc{margin:0 0 .5rem;font-weight:700;color:var(--ink-soft)}
.crop-wrap{display:grid;place-items:center}
#cropCv{width:min(280px,100%);height:auto;aspect-ratio:1;border-radius:20px;cursor:grab;touch-action:none;background:#fff;border:2px solid var(--line)}
#cropCv.grab{cursor:grabbing}
#cropCv:focus-visible{outline:3px solid var(--ink);outline-offset:3px}
.crop-zoom{display:flex;align-items:center;gap:.8rem;font-weight:800;color:var(--ink-soft)}
.crop-zoom input{flex:1;accent-color:var(--pink-deep)}
.crop-act{display:flex;gap:.6rem;flex-wrap:wrap}
.pf-note{font-style:italic;color:var(--ink-soft);margin:0}
.pf-dl{width:100%;margin:.6rem 0 0;display:grid;gap:.4rem;text-align:left}
.pf-dl div{display:flex;justify-content:space-between;gap:1rem;background:var(--bg);border-radius:12px;padding:.5rem .8rem}
.pf-dl dt{font-weight:700;color:var(--ink-soft)}
.pf-dl dd{margin:0;font-weight:800;text-align:right;overflow-wrap:anywhere}
@media (max-width:900px){.pf-grid,.pf-fields{grid-template-columns:minmax(0,1fr)}.pf-card{position:static}}
/* mode otomatis */
.badge.q{background:var(--pink-deep);margin-left:.3rem}
.sw{appearance:none;border:2px solid var(--line);background:#fff;border-radius:999px;padding:.25rem .9rem .25rem .3rem;display:inline-flex;align-items:center;gap:.6rem;font-weight:800;color:var(--ink);cursor:pointer;font-family:inherit}
.sw i{width:44px;height:24px;border-radius:999px;background:var(--line);position:relative;transition:background .2s}
.sw i::after{content:"";position:absolute;top:3px;left:3px;width:18px;height:18px;border-radius:50%;background:#fff;box-shadow:0 1px 3px rgba(0,0,0,.25);transition:transform .2s}
.sw.on i{background:var(--mint-deep)}
.sw.on i::after{transform:translateX(20px)}
.sw:focus-visible{outline:3px solid var(--ink);outline-offset:3px}
.k-left{position:relative}
.k-row{display:flex;align-items:flex-end;gap:1.2rem;margin-bottom:-1.2rem;position:relative;z-index:1;min-height:0}
.k-queue{display:flex;align-items:flex-end;gap:.9rem;margin-bottom:0}
.k-queue:empty{display:none}
.kq-item{display:flex;flex-direction:column;align-items:center}
.kq-face{font-size:6rem;line-height:1;filter:drop-shadow(0 4px 0 rgba(70,53,110,.14))}
.kq-name{font-weight:800;font-size:.85rem;color:var(--ink-soft);margin-top:.2rem}
.kq-more{align-self:center;font-weight:800;font-size:.9rem;background:#fff;border-radius:999px;padding:.1rem .6rem;margin-bottom:2rem}
.k-cust.walk{will-change:transform;animation:walkIn var(--wd,1s) cubic-bezier(.4,.05,.3,1) both}
.k-cust.walk .k-face{will-change:transform;animation:hop calc(var(--wd,1s)/4) ease-in-out 4 alternate}
.k-queue.walkq{will-change:transform;animation:qIn var(--wd,1s) cubic-bezier(.4,.05,.3,1) both}
.arriving .bitem.place:not(.zap){animation:placeItem .42s cubic-bezier(.3,1.3,.5,1) backwards;animation-delay:calc(var(--wd,1s) + var(--i,0)*.09s)}
.kq-item.kq-new{animation:qNew .7s cubic-bezier(.2,.7,.3,1) both}
@keyframes walkIn{from{transform:translate3d(var(--dx,140px),0,0)}to{transform:translate3d(0,0,0)}}
@keyframes qIn{from{transform:translate3d(var(--qdx,0px),0,0)}to{transform:translate3d(0,0,0)}}
@keyframes placeItem{from{opacity:0;transform:translateY(-18px) scale(.6)}60%{opacity:1;transform:translateY(0) scale(1.1)}to{opacity:1;transform:none}}
@keyframes qNew{from{opacity:0;transform:translateX(160px)}to{opacity:1;transform:none}}
.k-cust.enter{animation:stepIn .5s ease-out}
@keyframes stepIn{from{transform:translateX(140px);opacity:0}}
@media (max-width:900px){.kasir-scene{grid-template-columns:minmax(0,1fr)}.k-right{padding-bottom:1rem}.scan-unit{width:84px}}
.panel{margin-top:1.4rem;background:#fff;border:2px solid var(--line);border-radius:var(--radius);padding:1.3rem 1.5rem;display:grid;gap:.9rem}
.panel h2{font-size:1.45rem}
.panel-row{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:1rem}
.panel-row > div{min-width:0;flex:1 1 320px}
.lbl{display:block;font-family:var(--f-display);font-weight:700;font-size:1.15rem;margin-bottom:.4rem}
.ansform{display:flex;flex-wrap:wrap;align-items:flex-end;gap:1rem}
.ansform>div{flex:0 1 340px;max-width:100%;min-width:0}
.money{display:flex;align-items:center;gap:.6rem;background:var(--bg);border:3px solid var(--lav);border-radius:16px;padding:.35rem .9rem;width:min(340px,100%)}
.money > span{font-family:var(--f-display);font-weight:700;color:var(--ink-soft)}
.money input{border:0;background:transparent;font-family:var(--f-display);font-weight:800;font-size:1.8rem;color:var(--ink);width:100%;min-width:0;outline:none;font-variant-numeric:tabular-nums}
.money:focus-within{border-color:var(--pink-deep)}
.msg{min-height:1.4em;font-weight:800;color:var(--bad)}
.shake{animation:shake .35s}
@keyframes shake{20%,60%{transform:translateX(-7px)}40%,80%{transform:translateX(7px)}}
.pay-grid{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:1.2rem;align-items:center}
.cust{display:flex;align-items:center;gap:1rem}
.cust-face{flex:none;font-size:3.2rem;background:var(--butter-soft);width:84px;height:84px;border-radius:50%;display:grid;place-items:center;box-shadow:0 4px 0 var(--butter)}
.bubble{background:var(--pink-soft);border-radius:18px 18px 18px 4px;padding:.6rem 1rem;line-height:1.3}
.cash{display:flex;flex-wrap:wrap;gap:.5rem}
.note{display:grid;place-items:center;min-width:108px;height:52px;border-radius:8px;font-family:var(--f-mono);font-weight:500;font-size:.95rem;border:2px solid rgba(70,53,110,.28);transform:rotate(var(--r,0deg));padding:0 .6rem}
.note:nth-child(odd){--r:-2deg}.note:nth-child(even){--r:2deg}
.n100000{background:#FFB3C0}.n50000{background:var(--sky)}.n20000{background:var(--mint)}.n10000{background:var(--lav)}.n5000{background:var(--peach)}.n2000{background:#DDD6EE}.n1000{background:var(--butter)}
.sums{display:flex;flex-wrap:wrap;gap:.8rem;margin:0}
.sums div{background:var(--bg);border-radius:14px;padding:.5rem 1rem}
.sums dt{font-size:.85rem;color:var(--ink-soft);font-weight:800}
.sums dd{margin:0;font-family:var(--f-display);font-weight:800;font-size:1.5rem;font-variant-numeric:tabular-nums}
.done-head{display:flex;align-items:center;gap:1rem}
.done-head .big{font-size:3rem}
.receipt{max-width:400px;background:#fff;border:2px dashed var(--lav);border-radius:14px;padding:1rem 1.2rem;font-family:var(--f-mono);font-size:.9rem;display:grid;gap:.25rem}
.receipt .c{text-align:center;font-weight:500}
.receipt hr{border:0;border-top:2px dashed var(--line);margin:.4rem 0;width:100%}
.receipt .r{display:flex;justify-content:space-between;gap:1rem;font-variant-numeric:tabular-nums}
.receipt .r span:first-child{overflow-wrap:anywhere}
.receipt .tot{font-weight:500;font-size:1rem}
.actions{display:flex;flex-wrap:wrap;gap:.8rem}

/* admin */
.admin{display:grid;gap:1.4rem;margin-top:1.2rem}
.form-grid{display:grid;grid-template-columns:minmax(0,1.5fr) minmax(0,1fr) minmax(0,1fr);gap:1rem}
.fld{display:grid;gap:.3rem;min-width:0}
.fld > span{font-weight:800;font-size:.9rem;color:var(--ink-soft)}
.in{font-weight:700;font-size:1rem;color:var(--ink);background:var(--bg);border:2px solid var(--line);border-radius:12px;padding:.55rem .75rem;width:100%;min-width:0}
.in:focus{outline:none;border-color:var(--pink-deep)}
select.in{cursor:pointer}
.emoji-grid{display:flex;flex-wrap:wrap;gap:.3rem;margin-top:.3rem}
.eb{width:44px;height:44px;font-size:1.5rem;background:var(--bg);border:2px solid transparent;border-radius:12px;cursor:pointer;display:grid;place-items:center;padding:0}
.eb:hover{border-color:var(--pink)}
.eb.on{background:var(--pink-soft);border-color:var(--pink-deep)}
.addrow{display:flex;flex-wrap:wrap;align-items:center;gap:1rem;margin-top:1rem}
.tbl-wrap{overflow-x:auto}
.tbl{width:100%;min-width:640px;border-collapse:separate;border-spacing:0 .45rem}
.tbl th{text-align:left;font-size:.85rem;color:var(--ink-soft);font-weight:800;padding:0 .5rem}
.tbl td{padding:.35rem .5rem;background:var(--bg)}
.tbl td:first-child{border-radius:14px 0 0 14px}
.tbl td:last-child{border-radius:0 14px 14px 0;text-align:right}
.emo{width:68px;text-align:center;font-size:1.5rem;padding:.3rem}
.danger{--sh:#E9B5C6}
.admin-top{display:flex;flex-wrap:wrap;gap:1rem;align-items:center;justify-content:space-between}

/* kotak pencarian */
.searchbar{margin:1rem 0 .4rem}
.search{display:flex;align-items:center;gap:.6rem;background:#fff;border:3px solid var(--lav);border-radius:999px;padding:.3rem .45rem .3rem 1.1rem;max-width:560px;width:100%}
.search:focus-within{border-color:var(--pink-deep)}
.search svg{flex:none;width:1.3rem;height:1.3rem;color:var(--ink-soft)}
.sinput{flex:1;min-width:0;border:0;background:transparent;font-size:1.05rem;font-weight:700;color:var(--ink);outline:none;padding:.35rem 0}
.sinput::placeholder{color:var(--ink-soft);opacity:.75}
.sclear{flex:none;border:0;background:var(--lav-soft);color:var(--ink);width:2rem;height:2rem;border-radius:50%;cursor:pointer;font-size:1.2rem;line-height:1;display:grid;place-items:center;padding:0}
.sclear:hover{background:var(--pink-soft)}
.sr-only{position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0 0 0 0);white-space:nowrap}
.tbl td.nores{text-align:center;border-radius:14px;color:var(--ink-soft);padding:1.2rem}

.empty{text-align:center;display:grid;gap:.8rem;justify-items:center;margin-top:2rem;padding:2rem 1.2rem}
.empty .big{font-size:3.4rem}

#toast{position:fixed;left:50%;bottom:calc(1.4rem + env(safe-area-inset-bottom,0px));transform:translate(-50%,20px);background:var(--ink);color:#fff;padding:.7rem 1.3rem;border-radius:999px;font-weight:800;opacity:0;pointer-events:none;transition:opacity .25s,transform .25s;z-index:60;max-width:calc(100% - 32px);text-align:center}
#toast.show{opacity:1;transform:translate(-50%,0)}
.overlay{position:fixed;inset:0;z-index:50;background:rgba(70,53,110,.5);display:grid;place-items:center;padding:16px}
.modal{background:#fff;border-radius:26px;padding:1.6rem;max-width:460px;width:100%;display:grid;gap:.9rem;box-shadow:0 10px 0 var(--lav)}
.modal h2{font-size:1.6rem}
.confetti{position:fixed;inset:0;pointer-events:none;overflow:hidden;z-index:70}
.confetti i{position:absolute;top:-20px;width:10px;height:14px;border-radius:3px;will-change:transform;animation:fall var(--t) linear var(--d) forwards}
@keyframes fall{to{transform:translateY(105vh) rotate(720deg)}}

/* layar loading */
.loader{position:fixed;inset:0;z-index:80;display:grid;place-items:center;padding:16px;overflow:hidden;opacity:0;transition:opacity .3s ease;
  background:
    radial-gradient(circle at 15% 15%,var(--pink-soft) 0 18%,transparent 42%),
    radial-gradient(circle at 88% 22%,var(--sky-soft) 0 16%,transparent 40%),
    radial-gradient(circle at 80% 92%,var(--mint-soft) 0 18%,transparent 44%),
    radial-gradient(circle at 12% 88%,var(--butter-soft) 0 14%,transparent 38%),var(--bg)}
.loader.in{opacity:1}
.ld-card{position:relative;z-index:1;text-align:center;display:grid;justify-items:center;gap:.8rem;width:min(360px,100%)}
.ld-rainbow svg{width:84px;height:63px;display:block}
.ld-stage{position:relative;width:180px;height:140px}
.ld-cart{position:absolute;left:50%;bottom:0;font-size:5rem;line-height:1;transform:translateX(-50%);filter:drop-shadow(0 5px 0 rgba(70,53,110,.14));animation:ldRoll 1.1s ease-in-out infinite alternate}
.ld-drop{position:absolute;left:calc(50% + var(--x,0px));top:0;font-size:2.1rem;line-height:1;opacity:0;animation:ldDrop 1.2s ease-in infinite;animation-delay:var(--d,0s)}
.ld-title{font-size:clamp(2rem,8vw,2.6rem)}
.ld-title span{display:inline-block;background:var(--pink);padding:.02em .22em .08em;border-radius:.28em;transform:rotate(-2deg);margin-left:.15em}
.ld-msg{font-weight:800;color:var(--ink-soft);min-height:1.5em}
.ld-bar{width:100%;height:20px;background:#fff;border:3px solid var(--lav);border-radius:999px;overflow:hidden}
.ld-bar i{display:block;height:100%;width:0;border-radius:999px;background:linear-gradient(90deg,var(--pink),var(--butter),var(--mint),var(--sky));animation:ldFill var(--ld,2.6s) cubic-bezier(.3,.5,.4,1) forwards}
.loader .float{z-index:0}
@keyframes ldRoll{from{transform:translateX(-50%) rotate(-5deg)}to{transform:translateX(-50%) rotate(5deg) translateY(-5px)}}
@keyframes ldDrop{0%{transform:translate(-50%,-10px) scale(1);opacity:0}15%{opacity:1}72%{transform:translate(-50%,64px) scale(.95);opacity:1}100%{transform:translate(-50%,84px) scale(.4);opacity:0}}
@keyframes ldFill{to{width:100%}}


@media (max-width:900px){
  .shop,.kasir-grid,.dash-grid,.title,.pay-grid,.form-grid{grid-template-columns:minmax(0,1fr)}
  .cart{position:static}
  .clist{max-height:none}
  .stats{grid-template-columns:repeat(2,minmax(0,1fr))}
  .hero{flex-direction:column;align-items:flex-start}
  .title-art{order:-1;max-width:380px}
}
@media (prefers-reduced-motion:reduce){
  *,*::before,*::after{animation-duration:.001ms!important;animation-iteration-count:1!important;transition-duration:.001ms!important}
}

.navlink.off{opacity:.45;cursor:not-allowed}
.modal{max-height:calc(100vh - 32px);overflow:auto}
.confetti{z-index:90}


/* hitung mundur 3-2-1 */
.cd-wrap{position:relative;z-index:1;display:grid;justify-items:center;gap:1.3rem;text-align:center;width:min(440px,100%)}
.cd-chip{margin:0;display:inline-flex;align-items:center;gap:.5rem;font-family:var(--f-display);font-weight:800;font-size:1.1rem;background:#fff;border:2px solid var(--lav);padding:.35rem 1.2rem;border-radius:999px;box-shadow:0 4px 0 var(--lav-soft)}
.cd-dial{--c:var(--lav);--sh:var(--lav-deep);position:relative;width:min(280px,72vw);aspect-ratio:1;display:grid;place-items:center}
.cd-svg{position:absolute;inset:0;width:100%;height:100%;transform:rotate(-90deg);overflow:visible}
.cd-track{fill:none;stroke:#fff;stroke-width:14}
.cd-arc{fill:none;stroke:var(--c);stroke-width:14;stroke-linecap:round;stroke-dasharray:553;stroke-dashoffset:553}
.cd-arc.run{animation:cdArc var(--step,.9s) linear forwards}
.cd-core{position:absolute;inset:11%;border-radius:50%;background:#fff;display:grid;place-items:center;box-shadow:0 10px 0 var(--lav-soft),0 22px 36px rgba(70,53,110,.18)}
.cd-num{display:block;font-family:var(--f-display);font-weight:800;font-size:clamp(5rem,22vw,8.5rem);line-height:1;color:var(--sh)}
.cd-num.go{font-size:clamp(2.6rem,11vw,3.8rem)}
.cd-num.pop{animation:cdPop .6s cubic-bezier(.2,1.4,.4,1)}
.cd-ripple{position:absolute;inset:0;border-radius:50%;border:6px solid var(--c);opacity:0;pointer-events:none}
.cd-ripple.go{animation:cdRip .9s ease-out}
.cd-msg{margin:0;font-family:var(--f-display);font-weight:700;font-size:1.3rem;min-height:1.6em}
.cd-queue{display:flex;gap:1rem;font-size:3rem;line-height:1;min-height:3.4rem}
.cd-queue span{opacity:.25;transform:translateY(8px) scale(.7);filter:grayscale(1);transition:opacity .35s,transform .35s cubic-bezier(.3,1.5,.5,1),filter .35s}
.cd-queue span.on{opacity:1;transform:none;filter:none}
.cd-queue.rush span{animation:cdHop .45s ease-in-out infinite alternate}
.cd-queue.rush span:nth-child(2){animation-delay:.12s}
.cd-queue.rush span:nth-child(3){animation-delay:.24s}
@keyframes cdArc{from{stroke-dashoffset:553}to{stroke-dashoffset:0}}
@keyframes cdPop{0%{transform:scale(.3);opacity:0}60%{transform:scale(1.12);opacity:1}100%{transform:scale(1)}}
@keyframes cdRip{from{opacity:.55;transform:scale(1)}to{opacity:0;transform:scale(1.5)}}
@keyframes cdHop{to{transform:translateY(-10px)}}

/* papan status */
.ch-hud{position:sticky;top:4.6rem;z-index:15;display:flex;flex-wrap:wrap;align-items:center;gap:.8rem 1.2rem;background:#fff;border:2px solid var(--lav);border-radius:26px;padding:.7rem 1rem;margin:0 0 1rem;box-shadow:0 5px 0 var(--lav-soft)}
.ch-ring{--p:100;--rc:var(--mint-deep);flex:none;width:86px;height:86px;border-radius:50%;background:conic-gradient(var(--rc) calc(var(--p)*1%),var(--line) 0);display:grid;place-items:center}
.ch-ring-in{width:68px;height:68px;border-radius:50%;background:#fff;display:grid;place-content:center;text-align:center;line-height:1.1}
.ch-ring-in b{font-family:var(--f-mono);font-weight:500;font-size:1.35rem;font-variant-numeric:tabular-nums}
.ch-ring-in small{font-size:.55rem;font-weight:800;letter-spacing:.06em;color:var(--ink-soft)}
.ch-cards{flex:1 1 320px;display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.6rem;min-width:0}
.ch-st{display:flex;align-items:center;gap:.6rem;border-radius:18px;padding:.5rem .75rem;min-width:0}
.ch-st.a{background:var(--mint-soft)}.ch-st.b{background:var(--sky-soft)}.ch-st.c{background:var(--butter-soft)}.ch-st.d{background:var(--pink-soft)}
.ch-ic{flex:none;font-size:1.6rem;line-height:1}
.ch-st b{display:block;font-family:var(--f-display);font-weight:800;font-size:1.55rem;line-height:1;font-variant-numeric:tabular-nums}
.ch-st small{display:block;font-size:.72rem;font-weight:800;color:var(--ink-soft)}
.ch-st.bump{animation:chBump .4s cubic-bezier(.3,1.5,.5,1)}
@keyframes chBump{40%{transform:scale(1.12)}}
.ch-hud.low{border-color:var(--bad);box-shadow:0 5px 0 #F7C9D6}
.ch-hud.low .ch-ring{--rc:var(--bad)}
.ch-hud.low .ch-ring-in b{color:var(--bad)}
@media (prefers-reduced-motion:no-preference){.ch-hud.low .ch-ring{animation:chPulse .8s ease-in-out infinite alternate}}
@keyframes chPulse{to{transform:scale(1.07)}}

/* halaman tantangan */
.ch-hero{display:flex;align-items:center;justify-content:space-between;gap:1rem;background:linear-gradient(135deg,var(--pink-soft),var(--butter-soft) 60%,var(--mint-soft));border:2px solid var(--line);border-radius:28px;padding:1.4rem 1.7rem;overflow:hidden}
.ch-hero .ptitle{margin:0 0 .3rem}
.ch-hero .sub{margin:0;max-width:34rem}
.ch-eyebrow{display:inline-block;background:#fff;border-radius:999px;padding:.2rem .9rem;font-weight:800;font-size:.85rem;color:var(--ink-soft);margin-bottom:.6rem}
.ch-art{flex:none;font-size:3.6rem;line-height:1;display:flex;gap:.3rem;filter:drop-shadow(0 6px 0 rgba(70,53,110,.12))}
.ch-grid{display:grid;grid-template-columns:minmax(0,1.1fr) minmax(0,1fr);gap:1.2rem;margin-top:1.2rem;align-items:start}
.ch-opts{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:.7rem;margin:.3rem 0 .9rem}
.ch-opt{position:relative;display:grid;justify-items:center;gap:.1rem;text-align:center;background:#fff;border:2px solid var(--line);border-radius:20px;padding:.9rem .4rem;cursor:pointer;font-family:inherit;color:var(--ink);transition:transform .15s,border-color .15s,background .15s}
.ch-opt:hover{border-color:var(--pink);transform:translateY(-2px)}
.ch-opt .ic{font-size:2rem;line-height:1.1}
.ch-opt b{font-family:var(--f-display);font-size:1.3rem}
.ch-opt small{font-weight:700;color:var(--ink-soft)}
.ch-opt.on{background:var(--pink-soft);border-color:var(--pink-deep);box-shadow:0 4px 0 var(--pink)}
.ch-opt.on::after{content:"✓";position:absolute;top:.4rem;right:.45rem;width:22px;height:22px;border-radius:50%;background:var(--pink-deep);color:#fff;display:grid;place-items:center;font-size:.8rem;font-weight:800}
.ch-opt:focus-visible{outline:3px solid var(--ink);outline-offset:3px}
.ch-target{background:var(--butter-soft);border-radius:14px;padding:.6rem .9rem;font-weight:700;margin:0 0 1rem}
.ch-steps{list-style:none;margin:0 0 1.3rem;padding:0;display:grid;gap:.5rem;counter-reset:cs}
.ch-steps li{counter-increment:cs;display:flex;gap:.8rem;align-items:center;background:var(--bg);border-radius:14px;padding:.55rem .8rem;font-weight:700;color:var(--ink-soft)}
.ch-steps li::before{content:counter(cs);flex:none;width:28px;height:28px;border-radius:50%;background:var(--lav);color:var(--ink);display:grid;place-items:center;font-family:var(--f-display);font-weight:800}
.ch-steps li:last-child::before{content:"★";background:var(--butter)}
.ch-go{font-size:1.5rem;padding:.8rem 2.2rem .85rem;animation:none}
.ch-recs,.ch-runs{list-style:none;margin:0;padding:0;display:grid;gap:.6rem}
.ch-rc{display:flex;align-items:center;gap:.9rem;background:var(--bg);border-radius:18px;padding:.65rem 1rem}
.ch-rc .medal{flex:none;width:46px;height:46px;border-radius:50%;display:grid;place-items:center;font-size:1.5rem;background:var(--butter)}
.ch-rc.d120 .medal{background:var(--sky)}
.ch-rc.d180 .medal{background:var(--pink)}
.ch-rc.none .medal{background:#fff;filter:grayscale(1);opacity:.6}
.ch-rc .t{font-family:var(--f-display);font-weight:800;font-size:1.05rem}
.ch-rc small{display:block;color:var(--ink-soft);font-weight:700}
.ch-rc .sc{margin-left:auto;text-align:right;font-family:var(--f-display);font-weight:800;font-size:1.6rem;line-height:1}
.ch-rc .sc small{font-size:.75rem}
.ch-sub{margin:1.3rem 0 .6rem}
.ch-run{display:grid;grid-template-columns:auto minmax(0,1fr) auto;gap:.8rem;align-items:center;border:2px solid var(--line);border-radius:16px;padding:.55rem .9rem}
.ch-run .st{font-size:1.05rem;letter-spacing:.05em;color:var(--butter-deep);white-space:nowrap}
.ch-run .st i{font-style:normal}.ch-run .st i.off{color:#DCD6EA}
.ch-run b{display:block;font-size:.95rem}
.ch-run small{color:var(--ink-soft);font-weight:700}
.ch-run .pt{font-family:var(--f-display);font-weight:800;font-size:1.3rem}

/* popup hasil / evaluasi */
.modal.res{padding:0;gap:0;max-width:580px;overflow:auto}
.res-head{text-align:center;padding:1.5rem 1.4rem 1.2rem;background:linear-gradient(160deg,var(--lav-soft),var(--sky-soft))}
.res-head.s1{background:linear-gradient(160deg,var(--butter-soft),var(--peach-soft))}
.res-head.s2{background:linear-gradient(160deg,var(--mint-soft),var(--sky-soft))}
.res-head.s3{background:linear-gradient(160deg,var(--butter-soft),var(--pink-soft))}
.res-emoji{font-size:3rem;line-height:1;display:inline-block;animation:resBob 1.6s ease-in-out infinite alternate}
.res-head h2{margin:.3rem 0 0;font-size:1.9rem}
.res-grade{margin:.1rem 0 .7rem;font-family:var(--f-display);font-weight:700;color:var(--ink-soft)}
.res-stars{display:flex;justify-content:center;gap:.4rem;font-size:3.2rem;line-height:1}
.res-stars span{color:#D8D0E8;opacity:0;transform:scale(.4);animation:resStar .55s cubic-bezier(.2,1.5,.4,1) forwards;animation-delay:calc(.25s + var(--i)*.22s)}
.res-stars span.on{color:var(--butter-deep);filter:drop-shadow(0 3px 0 rgba(232,185,58,.4))}
.ch-new{display:inline-block;margin-top:.7rem;background:var(--butter);border-radius:999px;padding:.2rem 1rem;font-family:var(--f-display);font-weight:800;box-shadow:0 3px 0 var(--butter-deep)}
@keyframes resStar{to{opacity:1;transform:scale(1)}}
@keyframes resBob{to{transform:translateY(-6px) rotate(5deg)}}
.res-body{padding:1.2rem 1.4rem 1.4rem;display:grid;gap:1.1rem}
.res-top{display:grid;grid-template-columns:1fr 1fr;gap:.8rem}
.res-score{background:var(--butter-soft);border-radius:20px;padding:.9rem 1rem;display:grid;align-content:center;justify-items:center;gap:.1rem}
.res-score small{font-weight:800;letter-spacing:.08em;color:var(--ink-soft)}
.res-score b{font-family:var(--f-display);font-weight:800;font-size:3.2rem;line-height:1;font-variant-numeric:tabular-nums}
.res-delta{font-weight:700;font-size:.82rem;color:var(--ink-soft);text-align:center}
.res-acc{background:var(--mint-soft);border-radius:20px;padding:.9rem 1rem;display:flex;align-items:center;gap:.9rem;justify-content:center}
.res-ring{--p:0;flex:none;width:92px;height:92px;border-radius:50%;background:conic-gradient(var(--mint-deep) calc(var(--p)*1%),#fff 0);display:grid;place-items:center}
.res-ring>div{width:70px;height:70px;border-radius:50%;background:#fff;display:grid;place-content:center;text-align:center;line-height:1.05}
.res-ring b{font-family:var(--f-display);font-size:1.3rem}
.res-ring small{font-size:.58rem;font-weight:800;color:var(--ink-soft)}
.res-bs{display:grid;gap:.3rem;font-weight:700}
.res-bs .ok{color:var(--good)}.res-bs .no{color:var(--bad)}
.res-tiles{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:.6rem}
.res-tile{background:var(--bg);border-radius:16px;padding:.6rem .4rem;text-align:center}
.res-tile .i{font-size:1.4rem;line-height:1.2}
.res-tile b{display:block;font-family:var(--f-display);font-weight:800;font-size:1.1rem;overflow-wrap:anywhere}
.res-tile small{font-weight:700;color:var(--ink-soft);font-size:.74rem}
.res-eval h3{margin:0 0 .5rem;font-size:1.15rem}
.res-eval ul{list-style:none;margin:0;padding:0;display:grid;gap:.45rem}
.res-eval li{display:flex;gap:.7rem;align-items:flex-start;background:var(--lav-soft);border-radius:14px;padding:.55rem .8rem;font-weight:600;line-height:1.4}
.res-eval li i{font-style:normal;font-size:1.2rem;flex:none}
.res-act{display:flex;flex-wrap:wrap;gap:.6rem}
.res-act .btn:disabled{opacity:.5;cursor:wait}

@media (max-width:900px){.ch-grid{grid-template-columns:minmax(0,1fr)}.ch-hud{top:5.6rem}.ch-cards{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media (max-width:560px){.ch-art{display:none}.res-top{grid-template-columns:1fr}.res-tiles{grid-template-columns:repeat(2,minmax(0,1fr))}}

/* ===== tambahan: dashboard, zoom foto, belanja ===== */
.d-level.link{cursor:pointer;transition:transform .12s,border-color .12s,box-shadow .12s}
.d-level.link:hover{transform:translateY(-2px);border-color:var(--pink);box-shadow:0 8px 0 var(--pink-soft)}
.d-level.link:focus-visible{outline:3px solid var(--ink);outline-offset:3px}
.d-range{display:block;color:var(--ink-soft);font-weight:700;font-size:.82rem;margin-top:.15rem}
.d-note{margin:.7rem 0 0;color:var(--ink-soft);background:var(--bg);border-radius:12px;padding:.6rem .9rem;text-align:center;font-weight:700;font-size:.9rem}
.d-col.today .d-lab{background:var(--pink);color:var(--ink);border-radius:999px;padding:0 .7rem}
.d-col.today .d-val{color:var(--pink-deep)}
.d-col.future .d-barwrap{opacity:.55}
.d-col.future .d-lab{opacity:.6}

.mascot.zoomable{border:0;padding:0;cursor:zoom-in;position:relative;font:inherit;transition:transform .15s}
.mascot.zoomable:hover{transform:scale(1.05)}
.mascot.zoomable:focus-visible{outline:3px solid var(--ink);outline-offset:3px}
.mascot.zoomable::after{content:"🔍 Perbesar";position:absolute;left:0;right:0;bottom:0;padding:.25rem 0 .35rem;font-size:.7rem;font-weight:800;font-family:var(--f-body);color:#fff;background:linear-gradient(transparent,rgba(70,53,110,.75));opacity:0;transition:opacity .15s}
.mascot.zoomable:hover::after,.mascot.zoomable:focus-visible::after{opacity:1}

.lightbox{position:fixed;inset:0;z-index:80;background:rgba(40,28,72,.88);backdrop-filter:blur(6px);display:grid;place-items:center;animation:lbIn .18s ease-out}
@keyframes lbIn{from{opacity:0}}
.lb-stage{position:absolute;inset:0;display:grid;place-items:center;overflow:hidden;touch-action:none;cursor:zoom-in}
.lb-stage.zoomed{cursor:grab}
.lb-stage.zoomed:active{cursor:grabbing}
.lb-img{width:min(88vw,74vh);height:min(88vw,74vh);object-fit:cover;border-radius:24px;box-shadow:0 20px 60px rgba(0,0,0,.45),0 0 0 6px rgba(255,255,255,.85);user-select:none;-webkit-user-drag:none;will-change:transform;transition:transform .12s ease-out}
.lb-close{position:absolute;top:calc(1rem + env(safe-area-inset-top,0px));right:1rem;width:46px;height:46px;border-radius:50%;border:0;background:#fff;color:var(--ink);font-size:1.8rem;line-height:1;cursor:pointer;box-shadow:0 4px 0 var(--pink-deep);display:grid;place-items:center;padding:0}
.lb-close:hover{background:var(--pink-soft)}
.lb-bar{position:absolute;left:50%;bottom:calc(2.6rem + env(safe-area-inset-bottom,0px));transform:translateX(-50%);display:flex;align-items:center;gap:.5rem;background:#fff;border-radius:999px;padding:.35rem .5rem;box-shadow:0 4px 0 var(--lav)}
.lb-bar button{border:0;background:var(--lav-soft);color:var(--ink);height:38px;min-width:38px;border-radius:999px;font-weight:800;font-size:1.3rem;cursor:pointer;padding:0 .6rem;display:grid;place-items:center}
.lb-bar button:hover{background:var(--pink-soft)}
.lb-bar .lb-reset{font-size:.9rem;font-family:var(--f-body)}
.lb-pct{min-width:3.4rem;text-align:center;font-weight:800;font-variant-numeric:tabular-nums}
.lb-hint{position:absolute;left:0;right:0;bottom:calc(.8rem + env(safe-area-inset-bottom,0px));text-align:center;color:rgba(255,255,255,.8);font-size:.82rem;font-weight:700;margin:0;padding:0 1rem}
.lb-close:focus-visible,.lb-bar button:focus-visible{outline:3px solid var(--butter);outline-offset:2px}

/* belanja */
.shop-hero{display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:1.4rem 1.8rem;border-radius:30px;background:linear-gradient(120deg,#fff 0%,var(--pink-soft) 55%,var(--lav-soft) 100%);border:2px solid var(--line);box-shadow:0 6px 0 var(--line);overflow:hidden;position:relative}
.shop-hero .ptitle{margin-bottom:.3rem}
.sh-eyebrow{display:inline-block;background:#fff;border-radius:999px;padding:.15rem .9rem;font-weight:800;font-size:.85rem;color:var(--pink-deep);box-shadow:0 2px 0 var(--pink-soft);margin-bottom:.5rem}
.sh-art{display:flex;gap:.3rem;font-size:3rem;flex:none}
.sh-art span{display:block;filter:drop-shadow(0 4px 0 rgba(70,53,110,.14))}
.sh-art span:nth-child(1){transform:rotate(-10deg)}.sh-art span:nth-child(3){transform:rotate(8deg) translateY(6px)}.sh-art span:nth-child(4){transform:rotate(-6deg)}
@media (prefers-reduced-motion:no-preference){
  .sh-art span{animation:floaty 3s ease-in-out infinite}
  .sh-art span:nth-child(2){animation-delay:-.7s}.sh-art span:nth-child(3){animation-delay:-1.4s}.sh-art span:nth-child(4){animation-delay:-2.1s}
}
@keyframes floaty{50%{translate:0 -8px}}
.cchips{display:flex;gap:.5rem;flex-wrap:wrap;margin:.7rem 0 .2rem}
.cchip{display:inline-flex;align-items:center;gap:.4rem;border:2px solid var(--line);background:#fff;border-radius:999px;padding:.35rem .9rem;font-weight:800;font-size:.95rem;cursor:pointer;transition:transform .1s,background .1s,border-color .1s}
.cchip small{background:var(--lav-soft);border-radius:999px;padding:0 .5rem;font-size:.78rem}
.cchip:hover{transform:translateY(-2px);border-color:var(--pink)}
.cchip.on{background:var(--pink);border-color:var(--pink-deep);box-shadow:0 3px 0 var(--pink-deep)}
.cchip.on small{background:#fff}
.cchip:focus-visible{outline:3px solid var(--ink);outline-offset:2px}

.shop{margin-top:1.1rem}
.shelves{gap:1.6rem}
.shelf{position:relative;background:linear-gradient(180deg,#fff 0%,var(--back) 70%);border:2px solid #fff;border-radius:30px;padding:0 1.2rem 1.1rem;margin-top:1rem;box-shadow:0 0 0 2px var(--line),0 10px 24px rgba(70,53,110,.07)}
.shelf-head{display:flex;align-items:center;justify-content:space-between;gap:.8rem;padding-top:.2rem}
.shelf-sign{display:inline-flex;align-items:center;gap:.5rem;margin:-1rem 0 .7rem;background:var(--sign);padding:.35rem 1.2rem .35rem .9rem;border-radius:16px;font-family:var(--f-display);font-weight:800;font-size:1.2rem;box-shadow:0 4px 0 var(--deep)}
.shelf-sign span{font-size:1.4rem}
.shelf-count{color:var(--ink-soft);font-weight:800;font-size:.85rem;background:#fff;border-radius:999px;padding:.1rem .8rem;margin-top:-.4rem}
.shelf-row{display:flex;gap:.9rem;overflow-x:auto;overflow-y:hidden;padding:.7rem .3rem 1.1rem;margin:0 -.3rem;scroll-snap-type:x proximity;-webkit-overflow-scrolling:touch;overscroll-behavior-x:contain;scrollbar-width:thin;scrollbar-color:var(--sign) transparent}
.shelf-row::-webkit-scrollbar{height:10px}
.shelf-row::-webkit-scrollbar-track{background:rgba(255,255,255,.7);border-radius:999px}
.shelf-row::-webkit-scrollbar-thumb{background:var(--sign);border-radius:999px}
.shelf-row>.prod{flex:0 0 8.6rem;scroll-snap-align:start}
.shelf-tools{display:flex;align-items:center;gap:.4rem;margin-top:-.4rem}
.shelf-tools .shelf-count{margin-top:0}
.sarr{width:34px;height:34px;border-radius:50%;border:2px solid var(--line);background:#fff;color:var(--ink);font-family:var(--f-display);font-weight:800;font-size:1.3rem;line-height:1;cursor:pointer;display:grid;place-items:center;padding:0 0 .12rem;box-shadow:0 3px 0 var(--sign);transition:transform .1s,opacity .15s}
.sarr:hover:not(:disabled){transform:translateY(-2px);border-color:var(--sign)}
.sarr:disabled{opacity:.35;cursor:default;box-shadow:none}
.sarr[hidden]{display:none}
.sarr:focus-visible{outline:3px solid var(--ink);outline-offset:2px}
.shelf-rail{height:14px;margin:.9rem -1.2rem -1.1rem;border-radius:0 0 28px 28px;background:linear-gradient(180deg,var(--wood),var(--wood-deep));box-shadow:inset 0 3px 0 rgba(255,255,255,.45)}
.prod{position:relative;width:auto;margin:0;background:#fff;border:2px solid var(--line);border-radius:22px;padding:.5rem .5rem .6rem;box-shadow:0 4px 0 var(--line);transition:transform .14s,box-shadow .14s,border-color .14s}
.prod:hover{transform:translateY(-4px);border-color:var(--sign);box-shadow:0 8px 0 var(--sign)}
.prod:active{transform:translateY(1px);box-shadow:0 2px 0 var(--sign)}
.prod.in-cart{border-color:var(--mint-deep);box-shadow:0 4px 0 var(--mint)}
.prod-stage{display:grid;place-items:center;width:100%;aspect-ratio:1.25;border-radius:16px;background:radial-gradient(circle at 50% 55%,#fff 0 28%,var(--back) 72%)}
.prod-pic{font-size:3.3rem;padding:0}
.prod:hover .prod-pic{transform:translateY(-4px) scale(1.12) rotate(-4deg)}
.tag{background:transparent;box-shadow:none;min-height:0;width:100%;padding:.5rem .1rem 0;gap:.3rem;border-radius:0}
.tag b{font-size:.9rem;line-height:1.15}
.tag i{background:var(--butter-soft);color:var(--ink);padding:.05rem .75rem;box-shadow:0 2px 0 var(--butter)}
.plus{position:absolute;top:.55rem;right:.55rem;z-index:1;width:30px;height:30px;border-radius:50%;background:var(--pink);color:var(--ink);font-family:var(--f-display);font-weight:800;font-size:1.3rem;line-height:1;display:grid;place-items:center;box-shadow:0 3px 0 var(--pink-deep);opacity:.0;transform:scale(.7);transition:opacity .14s,transform .14s}
.prod:hover .plus,.prod:focus-visible .plus{opacity:1;transform:scale(1)}
@media (hover:none){.plus{opacity:1;transform:none}}
.qty{top:.55rem;left:.55rem;right:auto;z-index:1;background:var(--mint);box-shadow:0 2px 0 var(--mint-deep)}

.cart{border-radius:28px;border:2px solid #fff;box-shadow:0 0 0 2px var(--line),0 10px 24px rgba(70,53,110,.08);background:linear-gradient(180deg,#fff,var(--pink-soft) 220%);gap:.9rem}
.cart-head{display:flex;align-items:center;justify-content:space-between;gap:.6rem}
.cart-head h2{font-size:1.5rem}
.cart-head .badge{background:var(--pink-deep);height:1.7rem;min-width:1.7rem;font-size:.95rem}
.cline{background:#fff;border:2px solid var(--line);border-radius:16px}
.empty-box{display:grid;gap:.25rem;justify-items:center;background:#fff;border:2px dashed var(--pink);border-radius:20px;padding:1.4rem .8rem}
.empty-box .eb-ic{font-size:3rem;line-height:1}
.empty-box b{font-family:var(--f-display);font-size:1.15rem;color:var(--ink)}
.cart-actions .btn.mint{font-size:1.2rem;padding:.95rem 1.4rem}
.cart-actions .btn[disabled]{opacity:.5;cursor:not-allowed;transform:none}
@media (max-width:900px){.shop-hero{padding:1.1rem 1.2rem}.sh-art{font-size:2.2rem}.shelf-row>.prod{flex-basis:7.6rem}}
@media (max-width:520px){.sh-art{display:none}}

/* ===== kelola barang: form tambah ===== */
.np{padding:1.5rem 1.7rem 1.6rem}
.np-head{display:flex;align-items:center;gap:1rem;margin-bottom:1.3rem;padding-bottom:1.1rem;border-bottom:2px dashed var(--line)}
.np-head h2{font-size:1.5rem}
.np-head .sub{font-size:.92rem}
.np-badge{flex:none;width:52px;height:52px;border-radius:16px;display:grid;place-items:center;background:var(--mint);color:var(--ink);font-family:var(--f-display);font-weight:800;font-size:1.8rem;line-height:1;box-shadow:0 4px 0 var(--mint-deep)}
.np-body{display:grid;grid-template-columns:minmax(0,1fr) 250px;gap:1.8rem;align-items:start}
.np-main{display:grid;gap:1rem;min-width:0}
.np-step{display:flex;align-items:center;gap:.6rem;font-family:var(--f-display);font-weight:800;font-size:1.1rem}
.np-step:not(:first-child){margin-top:.4rem}
.np-num{width:28px;height:28px;border-radius:50%;background:var(--pink);display:grid;place-items:center;font-size:.95rem;box-shadow:0 2px 0 var(--pink-deep)}
.np-fields{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.9rem 1rem}
.np-name{grid-column:1/-1}
.np .in{background:#fff;border-radius:14px;padding:.7rem .95rem}
.np .in:focus{border-color:var(--pink-deep);box-shadow:0 0 0 4px var(--pink-soft)}
.np select.in{appearance:none;-webkit-appearance:none;padding-right:2.4rem;background:#fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%236F5F95' stroke-width='3' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E") no-repeat right .8rem center/1.1rem}
.emoji-picker{display:grid;gap:.4rem;background:var(--bg);border:2px solid var(--line);border-radius:20px;padding:.9rem 1rem;max-height:300px;overflow:auto}
.eg{display:grid;grid-template-columns:8.5rem minmax(0,1fr);gap:.7rem;align-items:center;padding:.35rem 0}
.eg+.eg{border-top:2px dashed var(--line)}
.eg-label{display:flex;align-items:center;gap:.45rem;font-weight:800;font-size:.88rem;color:var(--ink-soft)}
.eg-label span{font-size:1.2rem}
.eg-row{display:flex;flex-wrap:wrap;gap:.4rem}
.emoji-picker .eb{width:46px;height:46px;border-radius:14px;background:#fff;border:2px solid var(--line);transition:transform .1s,border-color .1s}
.emoji-picker .eb:hover{transform:translateY(-2px);border-color:var(--pink)}
.emoji-picker .eb.on{background:var(--pink-soft);border-color:var(--pink-deep);box-shadow:0 3px 0 var(--pink-deep)}
.np-side{position:sticky;top:5.6rem;display:grid;gap:.7rem;justify-items:center;text-align:center;background:linear-gradient(180deg,var(--lav-soft),var(--pink-soft));border-radius:24px;padding:1.1rem 1rem 1.2rem}
.np-label{font-weight:800;font-size:.85rem;color:var(--ink-soft)}
.np-card{width:100%;display:grid;justify-items:center;gap:.4rem;background:#fff;border:2px solid var(--line);border-radius:22px;padding:.6rem .6rem .8rem;box-shadow:0 5px 0 var(--sign)}
.np-pic{display:grid;place-items:center;width:100%;aspect-ratio:1.25;font-size:3.6rem;border-radius:16px;background:radial-gradient(circle at 50% 55%,#fff 0 28%,var(--back) 72%)}
.np-card b{font-size:.98rem;line-height:1.2;overflow-wrap:anywhere}
.np-card i{font-style:normal;font-family:var(--f-display);font-weight:700;background:var(--butter-soft);border-radius:999px;padding:.05rem .8rem;box-shadow:0 2px 0 var(--butter)}
.np-rak{font-weight:800;font-size:.85rem;background:#fff;border-radius:999px;padding:.1rem .9rem}
.np-act{display:grid;gap:.5rem;width:100%;margin-top:.3rem}
.np-act .btn{width:100%}
.np-act .msg{min-height:1.2rem;font-size:.88rem}
@media (max-width:900px){.np-body{grid-template-columns:minmax(0,1fr)}.np-side{position:static}.eg{grid-template-columns:minmax(0,1fr)}.np-fields{grid-template-columns:minmax(0,1fr)}}
.tbl-wrap{margin-top:.2rem}

/* ===== popup edit barang ===== */
.rowact{display:inline-flex;gap:.5rem;align-items:center}
.modal.ed{padding:0;gap:0;max-width:640px}
.ed-head{display:flex;align-items:center;gap:1.1rem;padding:1.2rem 1.5rem;background:linear-gradient(160deg,var(--lav-soft),var(--pink-soft))}
.ed-head h2{font-size:1.5rem;margin-bottom:.15rem}
.ed-head .sub{font-size:.9rem}
.ed-card{flex:none;width:128px;padding:.5rem .5rem .6rem}
.ed-card .np-pic{font-size:2.6rem}
.ed-card b{font-size:.88rem}
.ed-card i{font-size:.85rem}
.ed-body{padding:1.2rem 1.5rem 1.5rem;display:grid;gap:1rem}
.ed .np-fields{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.9rem 1rem}
.ed .in{background:#fff;border-radius:14px;padding:.7rem .95rem}
.ed .in:focus{border-color:var(--pink-deep);box-shadow:0 0 0 4px var(--pink-soft)}
.ed select.in{appearance:none;-webkit-appearance:none;padding-right:2.4rem;background:#fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%236F5F95' stroke-width='3' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E") no-repeat right .8rem center/1.1rem}
.ed .emoji-picker{max-height:220px}
.ed-act{display:flex;flex-wrap:wrap;align-items:center;gap:.7rem}
@media (max-width:560px){.ed-head{flex-direction:column;align-items:flex-start}.ed .np-fields{grid-template-columns:minmax(0,1fr)}.eg{grid-template-columns:minmax(0,1fr)}}
:root{--ocean-soft:#D2F4F2;--ocean:#7FD9D3;--ocean-deep:#35B0A8}
</style>

<div id="app"></div>
<div id="modalRoot"></div>
<div id="toast" role="status" aria-live="polite"></div>

<script>window.__PRODUK__ = <?= json_encode($produk, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;</script>
<script>
(function(){
'use strict';

/* ---------- data ---------- */
var CATS=[
  {name:'Minuman',back:'--sky-soft',sign:'--sky',deep:'--sky-deep',ic:'🥤'},
  {name:'Camilan',back:'--butter-soft',sign:'--butter',deep:'--butter-deep',ic:'🍿'},
  {name:'Buah & Sayur',back:'--mint-soft',sign:'--mint',deep:'--mint-deep',ic:'🥬'},
  {name:'Seafood',back:'--ocean-soft',sign:'--ocean',deep:'--ocean-deep',ic:'🦐'},
  {name:'Roti & Susu',back:'--peach-soft',sign:'--peach',deep:'--peach-deep',ic:'🍞'},
  {name:'Rumah Tangga',back:'--lav-soft',sign:'--lav',deep:'--lav-deep',ic:'🧼'}
];
var DEFAULTS=[
  {id:'d1',name:'Susu Stroberi',price:6500,cat:'Minuman',emoji:'🥛'},
  {id:'d2',name:'Jus Jeruk',price:5000,cat:'Minuman',emoji:'🧃'},
  {id:'d3',name:'Boba Cokelat',price:12000,cat:'Minuman',emoji:'🧋'},
  {id:'d4',name:'Air Mineral',price:3000,cat:'Minuman',emoji:'💧'},
  {id:'d5',name:'Kopi Susu',price:8000,cat:'Minuman',emoji:'☕'},
  {id:'d6',name:'Popcorn',price:7000,cat:'Camilan',emoji:'🍿'},
  {id:'d7',name:'Cokelat Batang',price:9500,cat:'Camilan',emoji:'🍫'},
  {id:'d8',name:'Lolipop',price:2500,cat:'Camilan',emoji:'🍭'},
  {id:'d9',name:'Biskuit Mentega',price:4500,cat:'Camilan',emoji:'🍪'},
  {id:'d10',name:'Permen Jelly',price:3500,cat:'Camilan',emoji:'🍬'},
  {id:'d11',name:'Apel Merah',price:4000,cat:'Buah & Sayur',emoji:'🍎'},
  {id:'d12',name:'Pisang Sesisir',price:12000,cat:'Buah & Sayur',emoji:'🍌'},
  {id:'d13',name:'Stroberi',price:15000,cat:'Buah & Sayur',emoji:'🍓'},
  {id:'d14',name:'Wortel',price:3000,cat:'Buah & Sayur',emoji:'🥕'},
  {id:'d15',name:'Semangka Mini',price:20000,cat:'Buah & Sayur',emoji:'🍉'},
  {id:'d16',name:'Brokoli',price:8500,cat:'Buah & Sayur',emoji:'🥦'},
  {id:'d17',name:'Roti Tawar',price:14000,cat:'Roti & Susu',emoji:'🍞'},
  {id:'d18',name:'Croissant',price:9000,cat:'Roti & Susu',emoji:'🥐'},
  {id:'d19',name:'Cupcake',price:8000,cat:'Roti & Susu',emoji:'🧁'},
  {id:'d20',name:'Keju Slice',price:11000,cat:'Roti & Susu',emoji:'🧀'},
  {id:'d21',name:'Telur Ayam',price:22000,cat:'Roti & Susu',emoji:'🥚'},
  {id:'d22',name:'Donat Gula',price:6000,cat:'Roti & Susu',emoji:'🍩'},
  {id:'d23',name:'Sabun Mandi',price:5500,cat:'Rumah Tangga',emoji:'🧼'},
  {id:'d24',name:'Tisu Gulung',price:9000,cat:'Rumah Tangga',emoji:'🧻'},
  {id:'d25',name:'Sampo',price:18000,cat:'Rumah Tangga',emoji:'🧴'},
  {id:'d26',name:'Sikat Gigi',price:7000,cat:'Rumah Tangga',emoji:'🪥'},
  {id:'d27',name:'Spons Cuci',price:3500,cat:'Rumah Tangga',emoji:'🧽'}
];
var EMOJIS=['🥛','🧃','🧋','💧','☕','🥤','🍿','🍫','🍭','🍪','🍬','🍦','🍎','🍌','🍓','🍊','🍇','🍑','🥕','🍉','🥦','🌽','🍞','🥐','🧁','🧀','🥚','🍩','🍰','🥨','🍙','🍜','🥫','🍯','🧂','🧼','🧻','🧴','🪥','🧽'];
var CUSTOMERS=[{e:'🐰',n:'Bubu si Kelinci'},{e:'🐱',n:'Mimi si Kucing'},{e:'🐻',n:'Beruang Boba'},{e:'🐼',n:'Panda Pipi'},{e:'🦊',n:'Rubi si Rubah'},{e:'🐥',n:'Piyo si Anak Ayam'}];
var SAYS=['Ini uangnya ya, Kak!','Aku bayar pakai ini ya.','Semoga kembaliannya gampang dihitung!'];
var LEVELS=[{n:'Kasir Magang',acc:0,min:0},{n:'Kasir Cekatan',acc:60,min:5},{n:'Kasir Juara',acc:75,min:15},{n:'Kasir Super',acc:85,min:30},{n:'Master Hitung',acc:95,min:50}];
var TIPS=[
  'Jumlahkan ribuan dulu, baru ratusan. Contoh 6.500 + 4.500: ribuannya 6 + 4 = 10, ratusannya 500 + 500 = 1.000, jadi 11.000.',
  'Cari pasangan yang menggenapkan. 3.500 + 6.500 = 10.000. Pasangan bulat membuat hitungan lebih ringan.',
  'Kembalian = uang bayar - total. Hitung naik bertahap. Total 23.500, bayar 50.000: 23.500 ke 24.000 (500), ke 30.000 (6.000), ke 50.000 (20.000). Jumlahkan: 500 + 6.000 + 20.000 = 26.500.',
  'Barang yang sama dibeli lebih dari satu? Kalikan dulu. 3 barang @ 4.500 = 3 x 4.500 = 13.500.',
  'Catat hasil sementara di kertas supaya tidak lupa. Jumlahkan per tiga barang, lalu gabungkan.'
];

/* ---------- util ---------- */
function $(s,r){return (r||document).querySelector(s)}
function $$(s,r){return Array.prototype.slice.call((r||document).querySelectorAll(s))}
function pick(a){return a[Math.floor(Math.random()*a.length)]}
function clone(o){return JSON.parse(JSON.stringify(o))}
function fmt(n){return String(Math.round(n)).replace(/\B(?=(\d{3})+(?!\d))/g,'.')}
function rp(n){return 'Rp '+fmt(n)}
function esc(s){return String(s).replace(/[&<>"']/g,function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]})}
function parseMoney(s){var d=String(s==null?'':s).replace(/\D/g,'');return d===''?null:Number(d)}
var LS={
  get:function(k,d){try{var v=localStorage.getItem(k);return v?JSON.parse(v):d}catch(e){return d}},
  set:function(k,v){try{localStorage.setItem(k,JSON.stringify(v))}catch(e){}}
};

/* ---------- state ---------- */
function norm(p){return {id:String(p.id),name:String(p.name),price:Math.round(Number(p.price)),cat:String(p.cat),emoji:String(p.emoji||'🛒')}}
function loadProducts(){
  var raw=window.__PRODUK__;
  return Array.isArray(raw)?raw.map(norm):[];
}
var S={
  view:'title',
  products:loadProducts(),
  stats:Object.assign({score:0,done:0,right:0,wrong:0,streak:0,best:0},LS.get('pm_stats',{})),
  cart:{},
  cartVer:0,
  co:null,
  hist:LS.get('pm_hist',[]),profile:Object.assign({nama:'',id:'',hp:'',catatan:'',foto:''},LS.get('pm_profile',{})),pickFoto:'',
  enter:null,queue:[],auto:LS.get('pm_auto',false)===true,nextIn:4,
  sound:LS.get('pm_sound',true)!==false,
  tip:Math.floor(Math.random()*TIPS.length),
  pickE:'🍎',q:'',aq:'',
  mascot:'🐰',
  ch:null,counting:false,
  chDur:[60,120,180].indexOf(LS.get('pm_chdur',60))>=0?LS.get('pm_chdur',60):60,
  chData:Object.assign({best:{},runs:[]},LS.get('pm_ch',{}))
};
function saveProducts(){S.cartVer++}
function api(body){
  var ctl=typeof AbortController!=='undefined'?new AbortController():null;
  var tm=setTimeout(function(){if(ctl)ctl.abort()},12000);
  return fetch('api.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(body),signal:ctl?ctl.signal:undefined})
    .then(function(r){return r.json().catch(function(){throw new Error('Jawaban server tidak terbaca.')})})
    .then(function(j){if(!j||!j.ok)throw new Error((j&&j.error)||'Gagal menyimpan.');return j})
    .then(function(j){clearTimeout(tm);return j},function(e){
      clearTimeout(tm);
      if(e&&e.name==='AbortError')throw new Error('Server tidak menjawab. Cek Apache dan MySQL di XAMPP.');
      throw e;
    });
}
function apiFail(e){toast('Gagal menyimpan: '+(e&&e.message?e.message:'cek koneksi server.'))}
function reloadProducts(){
  return fetch('api.php?action=list',{cache:'no-store'}).then(function(r){return r.json()}).then(function(j){
    if(j&&j.ok){S.products=j.produk.map(norm);saveProducts();render()}
  }).catch(function(){});
}
function saveStats(){LS.set('pm_stats',S.stats)}
function byId(id){for(var i=0;i<S.products.length;i++){if(S.products[i].id===id)return S.products[i]}return null}
function cartCount(){return Object.keys(S.cart).reduce(function(s,k){return s+S.cart[k]},0)}

/* ---------- sound ---------- */
var AC=null;
function tone(f,d,type,vol,when){
  if(!S.sound)return;
  try{
    AC=AC||new (window.AudioContext||window.webkitAudioContext)();
    var t=AC.currentTime+(when||0),o=AC.createOscillator(),g=AC.createGain();
    o.type=type||'sine';o.frequency.value=f;
    g.gain.setValueAtTime(vol||.12,t);g.gain.exponentialRampToValueAtTime(.0001,t+d);
    o.connect(g);g.connect(AC.destination);o.start(t);o.stop(t+d+.02);
  }catch(e){}
}
var sfx={
  add:function(){tone(660,.09,'triangle',.1)},
  scan:function(){tone(1300,.07,'square',.05)},
  ok:function(){tone(523,.12,'triangle',.12);tone(659,.12,'triangle',.12,.1);tone(784,.22,'triangle',.12,.2)},
  bad:function(){tone(220,.25,'sawtooth',.06);tone(165,.3,'sawtooth',.06,.15)},
  cd:function(go,k){var f=go?880:(k===undefined?520:[440,523,659][k]);tone(f,go?.5:.18,'triangle',.14);if(go){tone(1320,.5,'triangle',.1,.06);tone(1760,.4,'triangle',.07,.14)}}
};

/* ---------- ui helpers ---------- */
var tt;
function toast(m){var t=$('#toast');t.textContent=m;t.classList.add('show');clearTimeout(tt);tt=setTimeout(function(){t.classList.remove('show')},2300)}
function shake(el){if(!el)return;el.classList.remove('shake');void el.offsetWidth;el.classList.add('shake')}
function showModal(h){$('#modalRoot').innerHTML='<div class="overlay"><div class="modal" role="dialog" aria-modal="true" aria-labelledby="mt">'+h+'</div></div>';var b=$('#modalOk');if(b)b.focus()}
function closeModal(){$('#modalRoot').innerHTML=''}
function confetti(){
  if(window.matchMedia&&matchMedia('(prefers-reduced-motion: reduce)').matches)return;
  var box=document.createElement('div');box.className='confetti';
  var cols=['--pink','--butter','--mint','--sky','--lav','--peach'];
  for(var i=0;i<48;i++){
    var s=document.createElement('i');
    s.style.left=(Math.random()*100)+'%';
    s.style.background='var('+pick(cols)+')';
    s.style.setProperty('--t',(2+Math.random()*1.6)+'s');
    s.style.setProperty('--d',(Math.random()*.5)+'s');
    box.appendChild(s);
  }
  document.body.appendChild(box);
  setTimeout(function(){box.remove()},4300);
}

/* ---------- views: judul ---------- */
function titleHTML(){
  return '<section class="title">'+
  '<div class="title-copy">'+
    '<span class="eyebrow">Game kasir sambil belajar hitung</span>'+
    '<h1 class="wordmark"><span>Pelangi</span><span class="mart">Mart</span></h1>'+
    '<div class="rainbow" aria-hidden="true"></div>'+
    '<p class="lead">Pilih barang di rak, hitung total belanjaan sendiri, lalu hitung kembaliannya.</p>'+
    '<button class="btn play" data-act="play" id="playBtn"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 3.5v17l14-8.5z" fill="currentColor"/></svg>Main</button>'+
    '<p class="fine">Barang dan harga bisa diubah kapan saja di menu Kelola Barang.</p>'+
  '</div>'+
  '<div class="title-art">'+
    '<svg class="shop-svg" viewBox="0 0 460 400" role="img" aria-label="Gambar toko Pelangi Mart dengan atap bergaris merah muda">'+
      '<ellipse cx="230" cy="374" rx="205" ry="16" class="f-shadow"/>'+
      '<rect x="60" y="120" width="340" height="252" rx="22" class="f-white s-ink"/>'+
      '<rect x="120" y="42" width="220" height="58" rx="16" class="f-butter s-ink"/>'+
      '<text x="230" y="82" text-anchor="middle" class="svg-sign">Pelangi Mart</text>'+
      '<g class="s-ink">'+
        '<path d="M40 100H103V140Q71.5 168 40 140Z" class="f-pink"/>'+
        '<path d="M103 100H166V140Q134.5 168 103 140Z" class="f-white"/>'+
        '<path d="M166 100H229V140Q197.5 168 166 140Z" class="f-pink"/>'+
        '<path d="M229 100H292V140Q260.5 168 229 140Z" class="f-white"/>'+
        '<path d="M292 100H355V140Q323.5 168 292 140Z" class="f-pink"/>'+
        '<path d="M355 100H418V140Q386.5 168 355 140Z" class="f-white"/>'+
      '</g>'+
      '<rect x="88" y="190" width="150" height="116" rx="14" class="f-sky s-ink"/>'+
      '<line x1="96" y1="242" x2="230" y2="242" class="s-ink"/>'+
      '<line x1="96" y1="292" x2="230" y2="292" class="s-ink"/>'+
      '<text x="102" y="236" class="svg-emo">🥛</text><text x="140" y="236" class="svg-emo">🍞</text><text x="178" y="236" class="svg-emo">🍎</text>'+
      '<text x="102" y="286" class="svg-emo">🍓</text><text x="140" y="286" class="svg-emo">🧃</text><text x="178" y="286" class="svg-emo">🍭</text>'+
      '<rect x="270" y="196" width="102" height="176" rx="16" class="f-mint s-ink"/>'+
      '<rect x="284" y="212" width="74" height="62" rx="10" class="f-white s-ink"/>'+
      '<rect x="294" y="288" width="54" height="24" rx="8" class="f-butter s-ink"/>'+
      '<text x="321" y="305" text-anchor="middle" class="svg-small">BUKA</text>'+
      '<circle cx="354" cy="336" r="7" class="f-butter s-ink"/>'+
    '</svg>'+
    '<span class="float" style="left:2%;top:6%;--d:0s" aria-hidden="true">🍓</span>'+
    '<span class="float" style="right:3%;top:2%;--d:.8s" aria-hidden="true">🧁</span>'+
    '<span class="float" style="left:-2%;top:52%;--d:1.4s" aria-hidden="true">🍭</span>'+
    '<span class="float" style="right:-1%;top:46%;--d:.4s" aria-hidden="true">🥛</span>'+
    '<span class="float" style="right:12%;bottom:-2%;--d:1.1s" aria-hidden="true">🍌</span>'+
  '</div></section>';
}

/* ---------- menu ---------- */
var LOGO='<svg viewBox="0 0 40 30" aria-hidden="true"><path d="M4 28A16 16 0 0 1 36 28" fill="none" stroke="#FF9EC7" stroke-width="5" stroke-linecap="round"/><path d="M10 28A10 10 0 0 1 30 28" fill="none" stroke="#FFDF7A" stroke-width="5" stroke-linecap="round"/><path d="M16 28A4 4 0 0 1 24 28" fill="none" stroke="#8FE3C8" stroke-width="5" stroke-linecap="round"/></svg>';
function navHTML(){
  var n=cartCount();
  function link(v,label,extra){return '<button class="navlink '+(S.view===v?'on':'')+(chOn()&&v!=='kasir'?' off':'')+'" data-act="go" data-v="'+v+'"'+(S.view===v?' aria-current="page"':'')+'>'+label+(extra||'')+'</button>'}
  return '<header class="nav">'+
    '<button class="brand" data-act="go" data-v="dash" aria-label="Ke beranda">'+LOGO+'<span>Pelangi Mart</span></button>'+
    '<nav class="navlinks" aria-label="Menu utama">'+
      link('dash','Beranda')+link('shop','Belanja')+link('kasir','Kasir',(S.queue.length?'<span class="badge q" title="Pembeli mengantri bayar" aria-label="'+S.queue.length+' pembeli mengantri bayar">'+S.queue.length+'</span>':'')+(n?'<span class="badge" title="Barang di keranjang" aria-label="'+n+' barang di keranjang">'+n+'</span>':''))+link('tantang','Tantangan')+link('admin','Kelola Barang')+link('profil','Profil')+
    '</nav>'+
    '<div class="nav-right"><button class="btn ghost sm" data-act="sound">Suara: '+(S.sound?'Nyala':'Mati')+'</button><button class="btn ghost sm" data-act="title">Menu Utama</button></div>'+
  '</header>';
}
var lastNav='';
function updateNav(){
  var w=$('#navwrap');if(!w)return;
  var h=navHTML();
  if(h===lastNav&&w.firstChild)return; /* hindari bangun ulang menu (backdrop-filter mahal) saat animasi jalan */
  lastNav=h;w.innerHTML=h;
}

/* ---------- dasbor ---------- */
function levelInfo(){
  var st=S.stats,tot=st.right+st.wrong,acc=tot?Math.round(st.right/tot*100):0,i=0;
  LEVELS.forEach(function(l,k){if(acc>=l.acc&&tot>=l.min)i=k});
  var cur=LEVELS[i],nx=LEVELS[i+1],pct=100;
  if(nx)pct=Math.max(0,Math.min(99,Math.round(Math.min(nx.acc?acc/nx.acc:1,nx.min?tot/nx.min:1)*100)));
  return {cur:cur,nx:nx,pct:pct,acc:acc,tot:tot};
}
function levelHint(L){
  return L.nx?'Menuju '+L.nx.n+': butuh ketepatan '+L.nx.acc+'% dari minimal '+L.nx.min+' jawaban.':'Level tertinggi sudah tercapai!';
}
function safeFoto(v){return typeof v==='string'&&/^data:image\/(jpeg|png);base64,[A-Za-z0-9+\/=]+$/.test(v)?v:''}
function fotoInner(src){return src?'<img src="'+src+'" alt="Foto kasir">':'<span aria-hidden="true">'+S.mascot+'</span>'}
function kasirNo(){var n=String(S.profile.nama||'').trim();return n?n.toUpperCase().slice(0,16):'KASIR'}
function dayKey(t){var d=new Date(t);return d.getFullYear()+'-'+d.getMonth()+'-'+d.getDate()}
function recordSale(c){
  var g={},cat={};
  c.units.forEach(function(u){
    var o=g[u.id]||(g[u.id]={id:u.id,name:u.name,emoji:u.emoji,q:0,sum:0});o.q++;o.sum+=u.price;
    var p=byId(u.id),rk=p?p.cat:'Lainnya';cat[rk]=(cat[rk]||0)+u.price;
  });
  S.hist.push({t:Date.now(),total:c.total,n:c.units.length,who:c.who.n,e:c.who.e,src:c.src,cat:cat,lines:Object.keys(g).map(function(k){return g[k]})});
  if(S.hist.length>300)S.hist=S.hist.slice(-300);
  LS.set('pm_hist',S.hist);
}
function dashStats(){
  var items=0,prods={};
  S.hist.forEach(function(x){
    items+=x.n;
    (x.lines||[]).forEach(function(l){var o=prods[l.id]||(prods[l.id]={name:l.name,emoji:l.emoji,q:0});o.q+=l.q});
  });
  var top=Object.keys(prods).map(function(k){return prods[k]}).sort(function(a,b){return b.q-a.q}).slice(0,5);
  return {items:items,top:top,count:S.hist.length};
}
function dEmpty(t){return '<div class="d-empty">'+t+'</div>'}
function donutHTML(){
  var st=S.stats,tot=st.right+st.wrong,pct=tot?Math.round(st.right/tot*100):0;
  var bg=tot?'conic-gradient(var(--mint-deep) 0 '+pct+'%,var(--pink) '+pct+'% 100%)':'var(--line)';
  return '<div class="d-donutrow"><div class="d-donut" style="background:'+bg+'" role="img" aria-label="Ketepatan hitung '+(tot?pct+' persen':'belum ada data')+'"><div><b>'+(tot?pct+'%':'-')+'</b></div></div>'+
    '<ul class="d-legend"><li><i style="background:var(--mint-deep)"></i>Benar <b>'+st.right+'</b></li><li><i style="background:var(--pink)"></i>Salah <b>'+st.wrong+'</b></li><li><i style="background:var(--butter)"></i>Beruntun terbaik <b>'+st.best+'</b></li></ul></div>';
}
function topHTML(top){
  if(!top.length)return dEmpty('Belum ada barang terjual.');
  var max=top[0].q;
  return '<ol class="d-top">'+top.map(function(t){return '<li><span class="d-e" aria-hidden="true">'+esc(t.emoji)+'</span><div><b>'+esc(t.name)+'</b><small>'+t.q+' terjual</small><div class="d-ht"><i style="width:'+Math.round(t.q/max*100)+'%;background:var(--pink)"></i></div></div></li>'}).join('')+'</ol>';
}
function recentHTML(){
  var r=S.hist.slice(-5).reverse();
  if(!r.length)return dEmpty('Belum ada transaksi. Layani pembeli pertamamu di kasir.');
  return '<div class="tbl-wrap"><table class="tbl d-tbl"><thead><tr><th>Waktu</th><th>Pembeli</th><th>Barang</th></tr></thead><tbody>'+r.map(function(x){
    var w=new Date(x.t).toLocaleString('id-ID',{day:'2-digit',month:'short',hour:'2-digit',minute:'2-digit'});
    return '<tr><td>'+esc(w)+'</td><td>'+esc(x.e||'')+' '+esc(x.who||'Pembeli')+'</td><td>'+x.n+'</td></tr>'}).join('')+'</tbody></table></div>';
}
function dashHTML(){
  var L=levelInfo(),st=S.stats,tot=st.right+st.wrong,D=dashStats(),P=S.profile,qn=S.queue.length;
  var acc=tot?Math.round(st.right/tot*100)+'%':'-';
  function kpi(ic,bg,val,lab){return '<div class="d-kpi"><span class="d-ic" style="background:var('+bg+')" aria-hidden="true">'+ic+'</span><div><b>'+val+'</b><span>'+lab+'</span></div></div>'}
  return '<div class="d-head"><div><h1 class="ptitle">Beranda</h1><p class="sub">Halo, <b>'+esc(P.nama||'Kasir')+'</b>! Ini ringkasan permainanmu.</p></div>'+
      '<div class="d-quick"><button class="btn mint sm" data-act="go" data-v="kasir">Ke kasir'+(qn?' · '+qn+' antre':'')+'</button><button class="btn sm" data-act="go" data-v="shop">Belanja di rak</button><button class="btn butter sm" data-act="go" data-v="tantang">Mode tantangan</button></div></div>'+
    '<div class="d-kpis">'+
      kpi('🧾','--mint-soft',fmt(st.done),'Transaksi selesai')+
      kpi('📦','--sky-soft',fmt(D.items),'Barang terjual')+
      kpi('🎯','--pink-soft',acc,'Ketepatan hitung')+
      kpi('⭐','--butter-soft',fmt(st.score),'Skor')+
    '</div>'+
    '<div class="d-grid d-g1">'+
      '<section class="card"><h2>Barang terlaris</h2>'+topHTML(D.top)+'</section>'+
      '<section class="card"><h2>Ketepatan hitung</h2>'+donutHTML()+'</section>'+
    '</div>'+
    '<div class="d-grid d-g2">'+
      '<section class="card d-level link" role="link" tabindex="0" data-act="go" data-v="profil" title="Buka profil" aria-label="Buka halaman profil"><div class="mascot">'+fotoInner(safeFoto(P.foto))+'</div><div><span class="lv">Levelmu sekarang</span><h2>'+esc(L.cur.n)+'</h2><p class="d-acc">Ketepatan hitung <b>'+(L.tot?L.acc+'%':'-')+'</b> dari '+L.tot+' jawaban</p>'+
        '<div class="meter" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="'+L.pct+'" aria-label="Kemajuan menuju level berikutnya"><i style="width:'+L.pct+'%"></i></div>'+
        '<p class="sub">'+esc(levelHint(L))+'</p></div></section>'+
      '<section class="card" aria-label="Mode belanja otomatis"><h2>Mode belanja otomatis</h2><p class="sub" id="autoInfo">'+autoInfoText()+'</p><button class="sw'+(S.auto?' on':'')+'" role="switch" aria-checked="'+(S.auto?'true':'false')+'" data-act="auto"><i></i><span>'+(S.auto?'Nyala':'Mati')+'</span></button></section>'+
      '<section class="card tip"><h2>Tips hitung cepat</h2><p id="tipText">'+esc(TIPS[S.tip])+'</p><button class="btn butter sm" data-act="tip">Tips lain</button></section>'+
    '</div>'+
    '<div class="d-grid" style="grid-template-columns:minmax(0,1fr)">'+
      '<section class="card"><h2>Transaksi terakhir</h2>'+recentHTML()+'</section>'+
    '</div>';
}

/* ---------- profil ---------- */
function profilHTML(){
  var P=S.profile,L=levelInfo(),st=S.stats;
  S.pickFoto=safeFoto(P.foto);
  return '<h1 class="ptitle">Profil kasir</h1><p class="sub">Isi data dirimu sebagai kasir. Nama dan fotomu tampil di meja kasir dan di struk. Datanya tersimpan di browser ini.</p>'+
  '<div class="pf-grid">'+
    '<section class="card"><h2>Data kasir</h2><form data-form="profil" id="pfForm" autocomplete="off">'+
      '<div class="pf-fields">'+
        '<label class="fld"><span>Nama lengkap</span><input class="in" name="nama" maxlength="30" placeholder="Contoh: Siti Rahma" value="'+esc(P.nama)+'"></label>'+
        '<label class="fld"><span>ID kasir</span><input class="in" name="id" maxlength="12" placeholder="Contoh: KSR-001" value="'+esc(P.id)+'"></label>'+
        '<label class="fld" style="grid-column:1/-1"><span>Nomor HP</span><input class="in" type="tel" name="hp" maxlength="16" placeholder="Contoh: 0812 3456 7890" value="'+esc(P.hp)+'"></label>'+
      '</div>'+
      '<label class="fld" style="margin-top:1rem"><span>Catatan atau motto</span><textarea class="in" name="catatan" rows="2" maxlength="120" placeholder="Contoh: Teliti itu keren!">'+esc(P.catatan)+'</textarea></label>'+
      '<div class="fld" style="margin-top:1rem"><span>Foto kasir</span><div class="pf-photo-row"><div class="mascot pf-prev" id="pfPrev">'+fotoInner(S.pickFoto)+'</div>'+
        '<div class="pf-photo-btns"><label class="btn ghost sm pf-file">Pilih foto<input type="file" id="pfFoto" class="sr-only" accept="image/*"></label><button type="button" class="btn ghost sm" data-act="clearFoto">Hapus foto</button><small>Foto dipotong jadi persegi lalu disimpan di browser ini.</small></div></div></div>'+
      '<div class="addrow"><button class="btn mint" type="submit">Simpan profil</button><span class="msg" id="pfmsg" role="status"></span></div>'+
    '</form></section>'+
    '<aside class="card pf-card" aria-label="Kartu kasir">'+
      (S.pickFoto?'<button type="button" class="mascot zoomable" data-act="zoomFoto" aria-label="Perbesar foto profil" title="Klik untuk memperbesar">'+fotoInner(S.pickFoto)+'</button>':'<div class="mascot">'+fotoInner(S.pickFoto)+'</div>')+
      '<h2>'+esc(P.nama||'Nama belum diisi')+'</h2>'+
      (P.id?'<p class="sub">'+esc(P.id)+'</p>':'')+
      (P.catatan?'<p class="pf-note">“'+esc(P.catatan)+'”</p>':'')+
      '<dl class="pf-dl"><div><dt>Level</dt><dd>'+esc(L.cur.n)+'</dd></div><div><dt>Ketepatan hitung</dt><dd>'+(L.tot?L.acc+'%':'-')+'</dd></div><div><dt>Nomor HP</dt><dd>'+esc(P.hp||'Belum diisi')+'</dd></div><div><dt>Transaksi selesai</dt><dd>'+fmt(st.done)+'</dd></div></dl>'+
    '</aside>'+
  '</div>';
}
function updateFotoPrev(){var el=$('#pfPrev');if(el)el.innerHTML=fotoInner(S.pickFoto)}
function loadFoto(file){
  var msg=$('#pfmsg');if(!file||!msg)return;
  if(!/^image\//.test(file.type)){msg.textContent='File harus berupa gambar.';return}
  if(file.size>8*1024*1024){msg.textContent='Foto terlalu besar. Maksimal 8 MB.';return}
  var r=new FileReader();
  r.onerror=function(){msg.textContent='Foto tidak bisa dibaca.'};
  r.onload=function(){
    var img=new Image();
    img.onerror=function(){msg.textContent='Foto tidak bisa dibaca. Coba foto lain.'};
    img.onload=function(){openCrop(img)};
    img.src=r.result;
  };
  r.readAsDataURL(file);
}
var CROP=null,CV=280,CM=6;
function clampCrop(){var c=CROP,k=c.min*c.k,w=c.img.width*k,h=c.img.height*k;c.ox=Math.min(0,Math.max(CV-w,c.ox));c.oy=Math.min(0,Math.max(CV-h,c.oy))}
function drawCrop(){
  var cv=$('#cropCv');if(!cv||!CROP)return;
  var x=cv.getContext('2d'),c=CROP,k=c.min*c.k;
  x.clearRect(0,0,CV,CV);x.fillStyle='#fff';x.fillRect(0,0,CV,CV);
  x.drawImage(c.img,c.ox,c.oy,c.img.width*k,c.img.height*k);
  x.save();x.beginPath();x.rect(0,0,CV,CV);x.arc(CV/2,CV/2,CV/2-CM,0,Math.PI*2,true);x.fillStyle='rgba(42,33,80,.55)';x.fill('evenodd');x.restore();
  x.save();x.setLineDash([5,6]);x.lineWidth=1.5;x.strokeStyle='rgba(255,255,255,.7)';
  x.beginPath();x.moveTo(CV/2,CM);x.lineTo(CV/2,CV-CM);x.moveTo(CM,CV/2);x.lineTo(CV-CM,CV/2);x.stroke();x.restore();
  x.beginPath();x.arc(CV/2,CV/2,CV/2-CM,0,Math.PI*2);x.lineWidth=3;x.strokeStyle='#fff';x.stroke();
}
function setCropZoom(v){
  var c=CROP;v=Math.max(1,Math.min(3,v));
  var old=c.min*c.k,cx=(CV/2-c.ox)/old,cy=(CV/2-c.oy)/old;
  c.k=v;var n=c.min*v;c.ox=CV/2-cx*n;c.oy=CV/2-cy*n;clampCrop();drawCrop();
  var z=$('#cropZoom');if(z)z.value=Math.round(v*100);
}
function openCrop(img){
  var min=CV/Math.min(img.width,img.height);
  CROP={img:img,min:min,k:1,ox:(CV-img.width*min)/2,oy:(CV-img.height*min)/2};
  showModal('<h2 id="mt">Atur fotomu</h2><p class="sub">Geser foto dan atur zoom sampai wajahmu pas di tengah lingkaran.</p>'+
    '<div class="crop-wrap"><canvas id="cropCv" width="'+CV+'" height="'+CV+'" tabindex="0" aria-label="Area atur foto. Geser dengan mouse, sentuhan, atau tombol panah."></canvas></div>'+
    '<label class="crop-zoom"><span>Zoom</span><input type="range" id="cropZoom" min="100" max="300" value="100" aria-label="Zoom foto"></label>'+
    '<div class="crop-act"><button class="btn mint" id="modalOk" data-act="cropOk">Pakai foto</button><button class="btn ghost" data-act="cropCancel">Batal</button></div>');
  var cv=$('#cropCv'),z=$('#cropZoom'),drag=null;
  drawCrop();
  cv.addEventListener('pointerdown',function(e){drag={x:e.clientX,y:e.clientY,ox:CROP.ox,oy:CROP.oy};try{cv.setPointerCapture(e.pointerId)}catch(_){}cv.classList.add('grab')});
  cv.addEventListener('pointermove',function(e){
    if(!drag||!CROP)return;var f=CV/cv.getBoundingClientRect().width;
    CROP.ox=drag.ox+(e.clientX-drag.x)*f;CROP.oy=drag.oy+(e.clientY-drag.y)*f;clampCrop();drawCrop();
  });
  ['pointerup','pointercancel'].forEach(function(n){cv.addEventListener(n,function(){drag=null;cv.classList.remove('grab')})});
  cv.addEventListener('wheel',function(e){e.preventDefault();setCropZoom(CROP.k*(e.deltaY<0?1.08:1/1.08))},{passive:false});
  cv.addEventListener('keydown',function(e){
    var d={ArrowLeft:[12,0],ArrowRight:[-12,0],ArrowUp:[0,12],ArrowDown:[0,-12]}[e.key];
    if(d&&CROP){e.preventDefault();CROP.ox+=d[0];CROP.oy+=d[1];clampCrop();drawCrop()}
  });
  z.addEventListener('input',function(){setCropZoom(z.value/100)});
}
function applyCrop(){
  var c=CROP;if(!c)return;
  var k=c.min*c.k,o=document.createElement('canvas');o.width=o.height=256;
  o.getContext('2d').drawImage(c.img,(CM-c.ox)/k,(CM-c.oy)/k,(CV-2*CM)/k,(CV-2*CM)/k,0,0,256,256);
  S.pickFoto=o.toDataURL('image/jpeg',0.85);CROP=null;closeModal();updateFotoPrev();
  var m=$('#pfmsg');if(m)m.textContent='Foto sudah diatur. Klik Simpan profil untuk menyimpannya.';
}
function saveProfile(f){
  var fd=new FormData(f);
  function g(k,n){return String(fd.get(k)||'').replace(/\s+/g,' ').trim().slice(0,n)}
  S.profile={nama:g('nama',30),id:g('id',12),hp:g('hp',16).replace(/[^\d+ ()-]/g,''),catatan:g('catatan',120),foto:safeFoto(S.pickFoto)};
  LS.set('pm_profile',S.profile);
  render();toast('Profil tersimpan');
}


/* ---------- lightbox foto (zoom) ---------- */
var LB=null;
function closeLightbox(){
  if(!LB)return;
  document.removeEventListener('keydown',LB.key,true);
  document.body.style.overflow=LB.prevOverflow;
  LB.el.remove();
  var f=LB.from;LB=null;
  if(f&&f.isConnected)f.focus({preventScroll:true});
}
function openLightbox(src,from){
  closeLightbox();
  var el=document.createElement('div');
  el.className='lightbox';el.setAttribute('role','dialog');el.setAttribute('aria-modal','true');el.setAttribute('aria-label','Foto profil diperbesar');
  el.innerHTML='<div class="lb-stage"><img class="lb-img" alt="Foto kasir" draggable="false"></div>'+
    '<button type="button" class="lb-close" data-lb="close" aria-label="Tutup foto">&times;</button>'+
    '<div class="lb-bar"><button type="button" data-lb="out" aria-label="Perkecil">&minus;</button><span class="lb-pct" aria-live="polite">100%</span><button type="button" data-lb="in" aria-label="Perbesar">+</button><button type="button" class="lb-reset" data-lb="reset">Reset</button></div>'+
    '<p class="lb-hint">Scroll, cubit, atau klik dua kali untuk zoom. Geser untuk menggeser foto.</p>';
  document.body.appendChild(el);
  var img=el.querySelector('.lb-img'),stage=el.querySelector('.lb-stage'),pct=el.querySelector('.lb-pct');
  img.src=src;
  var z=1,x=0,y=0,MAX=5,pts={},moved=false,startDist=0,startZ=1,drag=null;
  function clamp(){
    var w=img.clientWidth,h=img.clientHeight,mx=Math.max(0,(w*z-w)/2),my=Math.max(0,(h*z-h)/2);
    x=Math.min(mx,Math.max(-mx,x));y=Math.min(my,Math.max(-my,y));
  }
  function apply(){
    if(z<=1.001){z=1;x=0;y=0}
    clamp();
    img.style.transform='translate('+x+'px,'+y+'px) scale('+z+')';
    pct.textContent=Math.round(z*100)+'%';
    stage.classList.toggle('zoomed',z>1);
  }
  function setZ(n){z=Math.min(MAX,Math.max(1,n));apply()}
  LB={el:el,from:from||null,prevOverflow:document.body.style.overflow,key:function(e){
    if(e.key==='Escape'){e.preventDefault();e.stopPropagation();closeLightbox()}
    else if(e.key==='+'||e.key==='='){setZ(z*1.25)}
    else if(e.key==='-'){setZ(z/1.25)}
    else if(e.key==='0'){setZ(1)}
  }};
  document.addEventListener('keydown',LB.key,true);
  document.body.style.overflow='hidden';
  el.addEventListener('click',function(e){
    var b=e.target.closest('[data-lb]');
    if(b){var a=b.dataset.lb;if(a==='close')closeLightbox();else if(a==='in')setZ(z*1.4);else if(a==='out')setZ(z/1.4);else if(a==='reset')setZ(1);return}
    if(!moved&&e.target!==img&&!e.target.closest('.lb-bar'))closeLightbox();
  });
  stage.addEventListener('wheel',function(e){e.preventDefault();setZ(z*(e.deltaY<0?1.15:1/1.15))},{passive:false});
  img.addEventListener('dblclick',function(){setZ(z>1.01?1:2.5)});
  stage.addEventListener('pointerdown',function(e){
    pts[e.pointerId]={x:e.clientX,y:e.clientY};moved=false;
    try{stage.setPointerCapture(e.pointerId)}catch(_){}
    var ids=Object.keys(pts);
    if(ids.length===2){var a=pts[ids[0]],b=pts[ids[1]];startDist=Math.hypot(a.x-b.x,a.y-b.y);startZ=z;drag=null}
    else drag={sx:e.clientX,sy:e.clientY,ox:x,oy:y};
  });
  stage.addEventListener('pointermove',function(e){
    if(!pts[e.pointerId])return;
    pts[e.pointerId]={x:e.clientX,y:e.clientY};
    var ids=Object.keys(pts);
    if(ids.length===2&&startDist){var a=pts[ids[0]],b=pts[ids[1]];moved=true;setZ(startZ*Math.hypot(a.x-b.x,a.y-b.y)/startDist)}
    else if(drag&&z>1){
      var dx=e.clientX-drag.sx,dy=e.clientY-drag.sy;
      if(Math.abs(dx)+Math.abs(dy)>4)moved=true;
      x=drag.ox+dx;y=drag.oy+dy;apply();
    }
  });
  function up(e){
    delete pts[e.pointerId];startDist=0;drag=null;
    var ids=Object.keys(pts);
    if(ids.length===1){drag={sx:pts[ids[0]].x,sy:pts[ids[0]].y,ox:x,oy:y}}
    setTimeout(function(){moved=false},0);
  }
  stage.addEventListener('pointerup',up);stage.addEventListener('pointercancel',up);
  img.addEventListener('load',apply);
  apply();
  el.querySelector('.lb-close').focus({preventScroll:true});
}

/* ---------- belanja ---------- */
function cartInner(){
  var ids=Object.keys(S.cart).filter(function(id){return byId(id)});
  var list=ids.length?'<ul class="clist">'+ids.map(function(id){var p=byId(id);return '<li class="cline"><span class="ce" aria-hidden="true">'+esc(p.emoji)+'</span><div class="cn"><b>'+esc(p.name)+'</b><small>'+rp(p.price)+' / buah</small></div>'+
    '<div class="stepq"><button data-act="dec" data-id="'+id+'" aria-label="Kurangi '+esc(p.name)+'">−</button><output>'+S.cart[id]+'</output><button data-act="inc" data-id="'+id+'" aria-label="Tambah '+esc(p.name)+'">+</button></div>'+
    '<button class="xbtn" data-act="rm" data-id="'+id+'" aria-label="Hapus '+esc(p.name)+' dari keranjang">×</button></li>'}).join('')+'</ul>'
    :'<div class="empty-box"><div class="eb-ic" aria-hidden="true">🧺</div><b>Keranjang masih kosong</b><span>Klik barang di rak untuk memasukkannya.</span></div>';
  return '<div class="cart-head"><h2><span aria-hidden="true">🛒</span> Keranjang</h2><span class="badge" aria-label="'+cartCount()+' barang">'+cartCount()+'</span></div>'+list+
    '<div class="cart-actions"><button class="btn mint" data-act="tokasir"'+(cartCount()?'':' disabled')+'>Ke kasir</button><button class="btn ghost sm" data-act="clear"'+(cartCount()?'':' disabled')+'>Kosongkan keranjang</button></div>';
}
function matchQ(p,q){if(!q)return true;return (p.name+' '+p.cat).toLowerCase().indexOf(q)!==-1}
function searchBox(id,val,ph){
  return '<div class="searchbar"><label class="sr-only" for="'+id+'">Cari barang</label><div class="search">'+
  '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><circle cx="10.5" cy="10.5" r="6.5"/><path d="M15.5 15.5L21 21"/></svg>'+
  '<input id="'+id+'" class="sinput" type="text" data-search="'+id+'" placeholder="'+ph+'" value="'+esc(val)+'" autocomplete="off">'+
  '<button type="button" class="sclear" data-act="clearSearch" aria-label="Hapus pencarian"'+(val?'':' hidden')+'>×</button></div></div>';
}
function shelvesHTML(){
  var q=S.q.trim().toLowerCase(),cf=S.cat||'';
  var shelves=CATS.map(function(c){
    if(cf&&c.name!==cf)return '';
    var items=S.products.filter(function(p){return p.cat===c.name&&matchQ(p,q)});
    if(!items.length)return '';
    return '<section class="shelf" id="rak-'+CATS.indexOf(c)+'" style="--back:var('+c.back+');--sign:var('+c.sign+');--deep:var('+c.deep+')" aria-label="Rak '+esc(c.name)+'">'+
      '<header class="shelf-head"><div class="shelf-sign"><span aria-hidden="true">'+c.ic+'</span>Rak '+esc(c.name)+'</div><span class="shelf-tools"><span class="shelf-count">'+items.length+' barang</span><button type="button" class="sarr l" data-act="shelfL" aria-label="Geser rak ke kiri" hidden>&lsaquo;</button><button type="button" class="sarr r" data-act="shelfR" aria-label="Geser rak ke kanan" hidden>&rsaquo;</button></span></header>'+
      '<div class="shelf-row">'+
      items.map(function(p){var n=S.cart[p.id]||0;return '<button class="prod'+(n?' in-cart':'')+'" data-act="add" data-id="'+p.id+'" aria-label="Masukkan '+esc(p.name)+' seharga '+rp(p.price)+' ke keranjang">'+
        '<span class="qty"'+(n?'':' hidden')+'>×'+n+'</span><span class="plus" aria-hidden="true">+</span>'+
        '<span class="prod-stage"><span class="prod-pic" aria-hidden="true">'+esc(p.emoji)+'</span></span>'+
        '<span class="tag"><b>'+esc(p.name)+'</b><i>'+rp(p.price)+'</i></span></button>'}).join('')+
      '</div><div class="shelf-rail" aria-hidden="true"></div></section>';
  }).join('');
  if(!S.products.length){
    return '<div class="card empty"><div class="big" aria-hidden="true">📦</div><h2>Rak masih kosong</h2><p class="sub">Tambahkan barang dulu di menu Kelola Barang.</p><button class="btn" data-act="go" data-v="admin">Kelola barang</button></div>';
  }
  if(!shelves){
    return '<div class="card empty"><div class="big" aria-hidden="true">🔍</div><h2>Barang tidak ditemukan</h2><p class="sub">Tidak ada barang atau rak yang cocok dengan "'+esc(S.q.trim())+'". Coba kata lain.</p></div>';
  }
  return shelves;
}
function catChipsHTML(){
  var cf=S.cat||'';
  function chip(c,label,ic,n){return '<button type="button" class="cchip'+(cf===c?' on':'')+'" data-act="shopCat" data-c="'+esc(c)+'" aria-pressed="'+(cf===c?'true':'false')+'"><span aria-hidden="true">'+ic+'</span>'+esc(label)+'<small>'+n+'</small></button>'}
  return '<div class="cchips" role="group" aria-label="Pilih rak">'+chip('','Semua rak','🌈',S.products.length)+CATS.map(function(c){
    var n=S.products.filter(function(p){return p.cat===c.name}).length;return n?chip(c.name,c.name,c.ic,n):''}).join('')+'</div>';
}
function shopHTML(){
  return '<section class="shop-hero"><div class="sh-text"><span class="sh-eyebrow">🛍️ Selamat berbelanja!</span><h1 class="ptitle">Belanja di rak</h1><p class="sub">Klik barang untuk memasukkannya ke keranjang. Harga ada di label bawah barang.</p></div>'+
      '<div class="sh-art" aria-hidden="true"><span>🍓</span><span>🛒</span><span>🧁</span><span>🥛</span></div></section>'+
    (S.products.length?searchBox('shopSearch',S.q,'Cari barang atau nama rak...')+catChipsHTML():'')+
    '<div class="shop"><div class="shelves" id="shelves">'+shelvesHTML()+'</div><aside class="cart" id="cart" aria-label="Keranjang belanja">'+cartInner()+'</aside></div>';
}
function shelfArrows(){
  $$('.shelf').forEach(function(sh){
    var r=$('.shelf-row',sh),l=$('.sarr.l',sh),n=$('.sarr.r',sh);if(!r)return;
    var over=r.scrollWidth>r.clientWidth+2;
    [l,n].forEach(function(b){if(b)b.hidden=!over});
    if(l)l.disabled=r.scrollLeft<=2;
    if(n)n.disabled=r.scrollLeft+r.clientWidth>=r.scrollWidth-2;
  });
}
document.addEventListener('scroll',function(e){if(e.target&&e.target.classList&&e.target.classList.contains('shelf-row'))shelfArrows()},true);
window.addEventListener('resize',shelfArrows);
function refreshShop(){
  var c=$('#cart');if(c)c.innerHTML=cartInner();
  $$('.prod').forEach(function(b){var q=S.cart[b.dataset.id]||0,el=$('.qty',b);b.classList.toggle('in-cart',!!q);if(el){el.hidden=!q;el.textContent='×'+q}});
  updateNav();
}

/* ---------- kasir ---------- */
function unitsFrom(basket){
  var units=[],k=0;
  Object.keys(basket).forEach(function(id){
    var p=byId(id);if(!p)return;
    for(var i=0;i<basket[id];i++)units.push({u:k++,id:p.id,name:p.name,price:p.price,emoji:p.emoji,scanned:false});
  });
  return units;
}
function makeCo(units,who,src,cust){
  return {units:units,scanned:0,lines:[],stage:'scan',total:units.reduce(function(s,u){return s+u.price},0),paid:0,change:0,who:who,say:'',attempts:0,gain:0,ver:S.cartVer,src:src,cust:cust||null,t0:Date.now()};
}
function startCheckout(){
  if(S.co&&S.co.stage!=='done'){
    if(S.co.src==='queue')return;
    if(S.co.ver===S.cartVer)return;
  }
  var units=unitsFrom(S.cart);
  if(units.length){S.co=makeCo(units,pick(CUSTOMERS),'cart');return}
  while(S.queue.length){
    var c=S.queue[0],u=unitsFrom(c.basket);
    if(u.length){S.co=makeCo(u,c.who,'queue',c);return}
    S.queue.shift();
  }
  S.co=null;
  updateNav();
}

/* ---------- mode belanja otomatis ---------- */
var MAXQ=8;
function rnd(a,b){return a+Math.floor(Math.random()*(b-a+1))}
function spawnCustomer(){
  if(!S.products.length)return;
  var pool=S.products.slice(),basket={},kinds=Math.min(rnd(3,5),pool.length),left=8,used={},dup=false;
  for(var i=0;i<kinds&&left>0&&pool.length;i++){
    var cand=pool.filter(function(x){return !used[x.price]});
    var src=cand.length?cand:pool; /* utamakan harga yang belum dipakai, supaya totalnya variatif */
    var p=src[Math.floor(Math.random()*src.length)];
    pool.splice(pool.indexOf(p),1);used[p.price]=1;
    var q=1;
    if(!dup&&left>1&&Math.random()<.15){q=2;dup=true} /* sesekali saja ada barang kembar, maksimal satu jenis */
    basket[p.id]=q;left-=q;
  }
  var who=pick(CUSTOMERS);
  S.queue.push({who:who,basket:basket});
  sfx.add();
  updateNav();
  var inf=$('#autoInfo');if(inf)inf.textContent=autoInfoText();
  if(S.view==='kasir'&&!S.co){startCheckout();render()}
  else if(S.view==='kasir'&&S.co&&S.co.stage==='done'&&S.co.left&&!cartCount()){
    advanceQueue(true); /* kasir kosong: pembeli datang dari kanan layar langsung menuju kasir */
  }else{
    var kq=$('#kq');
    if(kq){
      kq.innerHTML=queueStripHTML();
      var wn=S.queue.filter(function(x){return !S.co||x!==S.co.cust}).length,its=$$('.kq-item',kq);
      if(wn<=3&&its[wn-1])its[wn-1].classList.add('kq-new'); /* pembeli baru meluncur masuk ke antrean */
    }
    if(S.view!=='kasir')toast(who.e+' '+who.n+' datang dan antre di kasir');
  }
}
function advanceQueue(fromRight){
  var c=S.co;
  if(!c||c.stage!=='done'||!c.left||S.view!=='kasir'||!S.queue.length||cartCount())return;
  var kc=$('.k-cust'),qi=$$('.kq-item'),dx=0,qdx=0,wd=1;
  if(kc){
    if(fromRight===true||!qi.length){
      var sc=kc.closest('.kasir-scene'),r=kc.getBoundingClientRect();
      var lx=kc.classList.contains('leave')?(parseFloat(kc.style.getPropertyValue('--lx'))||0):0;
      dx=(sc?sc.getBoundingClientRect().right:window.innerWidth)-(r.left-lx)+8;wd=1.3;
    }else{
      dx=(qi[0].offsetLeft+qi[0].offsetWidth/2)-(kc.offsetLeft+kc.offsetWidth/2);
      if(qi[1])qdx=qi[1].offsetLeft-qi[0].offsetLeft;
    }
  }
  S.co=null;startCheckout();
  if(S.co)S.enter={dx:Math.round(dx),qdx:Math.round(qdx),wd:wd};
  render();
}
function autoTick(){
  if(!S.auto||chOn()||S.view==='title'||!S.products.length)return;
  S.nextIn--;
  if(S.nextIn>0)return;
  S.nextIn=rnd(12,22);
  if(S.queue.length>=MAXQ)return;
  spawnCustomer();
}
function autoInfoText(){
  var n=S.queue.length;
  return (S.auto?'Pembeli datang sendiri dan antre di kasir. ':'Nyalakan supaya pembeli belanja sendiri dan antre di kasir. ')+(n?n+' pembeli sedang mengantri bayar.':'Belum ada yang mengantri.');
}
function queueStripHTML(){
  var wait=S.queue.filter(function(x){return !S.co||x!==S.co.cust});
  if(!wait.length)return '';
  return wait.slice(0,3).map(function(x){return '<span class="kq-item"><span class="kq-face" aria-hidden="true">'+x.who.e+'</span><span class="kq-name">'+esc(x.who.n.split(' ')[0])+'</span></span>'}).join('')+(wait.length>3?'<span class="kq-more" title="Masih ada yang menunggu">+'+(wait.length-3)+'</span>':'');
}
function pickPaid(total){
  var set={};
  [1000,5000,10000,20000,50000,100000].forEach(function(u){var c=Math.ceil(total/u)*u;if(c>total)set[c]=1});
  var list=Object.keys(set).map(Number).sort(function(a,b){return a-b});
  if(!list.length)list=[total+10000];
  return pick(list.slice(0,3));
}
function stepsHTML(){
  var c=S.co,order=['scan','total','pay'],idx=c.stage==='done'?3:order.indexOf(c.stage);
  return ['Scan barang','Hitung total','Hitung kembalian'].map(function(l,i){
    return '<div class="step '+(i<idx?'past':'')+' '+(i===idx?'on':'')+'"><b>'+(i<idx?'✓':(i+1))+'</b>'+l+'</div>';
  }).join('');
}
function beltHTML(place){
  var rest=S.co.units.filter(function(u){return !u.scanned});
  if(!rest.length)return '<p class="belt-empty">Semua barang sudah di-scan.</p>';
  return rest.map(function(u,i){return '<button class="bitem'+(place?' place':'')+'"'+(place?' style="--i:'+i+'"':'')+' data-act="scan" data-u="'+u.u+'" aria-label="Scan barang">'+esc(u.emoji)+'</button>'}).join('');
}
function countText(){return 'Sudah di-scan '+S.co.scanned+' dari '+S.co.units.length+' barang'}
function lineHTML(u,n,isNew){return '<li'+(isNew?' class="new"':'')+'><span>'+n+'</span><span class="nm">'+esc(u.name)+'</span><span>'+rp(u.price)+'</span></li>'}
function linesHTML(){
  if(!S.co.lines.length)return '<li class="scr-hint" style="display:block">Menunggu barang di-scan...</li>';
  return S.co.lines.map(function(u,i){return lineHTML(u,i+1,false)}).join('');
}
function noteHTML(amount){
  var out='',left=amount;
  [100000,50000,20000,10000,5000,2000,1000].forEach(function(d){while(left>=d){out+='<span class="note n'+d+'">'+fmt(d)+'</span>';left-=d}});
  return out;
}
function receiptHTML(){
  var c=S.co,g={},order=[];
  c.units.forEach(function(u){if(!g[u.id]){g[u.id]={name:u.name,price:u.price,q:0};order.push(u.id)}g[u.id].q++});
  var d=new Date().toLocaleDateString('id-ID',{day:'2-digit',month:'short',year:'numeric'});
  return '<div class="receipt" aria-label="Struk belanja"><div class="c">PELANGI MART</div><div class="c">'+(S.profile.nama?'Kasir: '+esc(S.profile.nama):'Kasir')+'</div><div class="c">'+esc(d)+'</div><hr>'+
    order.map(function(id){var x=g[id];return '<div class="r"><span>'+esc(x.name)+' x'+x.q+'</span><span>'+fmt(x.price*x.q)+'</span></div>'}).join('')+
    '<hr><div class="r tot"><span>TOTAL</span><span>'+fmt(c.total)+'</span></div><div class="r"><span>BAYAR</span><span>'+fmt(c.paid)+'</span></div><div class="r tot"><span>KEMBALI</span><span>'+fmt(c.change)+'</span></div><hr><div class="c">Terima kasih sudah belanja!</div></div>';
}
function panelHTML(){
  var c=S.co;
  if(c.stage==='scan'){
    return '<div class="panel-row"><div><h2>Langkah 1 · Scan barang</h2><p class="sub">Klik barang di ban berjalan atau tekan Spasi. Nama dan harganya muncul di layar kasir. Catat dulu, hitungnya nanti.</p></div><button class="btn mint" data-act="scanNext">Scan barang berikutnya</button></div>';
  }
  if(c.stage==='total'){
    return '<h2>Langkah 2 · Hitung total</h2><p class="sub">Jumlahkan semua harga di layar. Total tidak muncul otomatis, jadi hitung dengan teliti. Kalau salah, belanja diulang dari awal.</p>'+
      '<form class="ansform" data-form="total" autocomplete="off"><div><label class="lbl" for="ans">Berapa total belanjanya?</label><div class="money"><span>Rp</span><input id="ans" class="mi" type="text" inputmode="numeric" placeholder="0"></div></div><button class="btn" type="submit">Periksa total</button></form><p class="msg" id="msg"></p>';
  }
  if(c.stage==='pay'){
    return '<h2>Langkah 3 · Hitung kembalian</h2>'+
      '<div class="pay-grid"><div class="cust"><div class="cust-face" aria-hidden="true">'+c.who.e+'</div><div class="bubble"><b>'+esc(c.who.n)+'</b><br>'+esc(c.say)+'</div></div>'+
      '<dl class="sums"><div><dt>Total belanja</dt><dd>'+rp(c.total)+'</dd></div><div><dt>Uang bayar</dt><dd>'+rp(c.paid)+'</dd></div></dl></div>'+
      '<div class="cash" aria-label="Uang dari pelanggan">'+noteHTML(c.paid)+'</div>'+
      '<form class="ansform" data-form="change" autocomplete="off"><div><label class="lbl" for="ans">Berapa kembaliannya?</label><div class="money"><span>Rp</span><input id="ans" class="mi" type="text" inputmode="numeric" placeholder="0"></div></div><button class="btn" type="submit">Periksa kembalian</button></form><p class="msg" id="msg"></p>';
  }
  return '<div class="done-head"><span class="big" aria-hidden="true">🎉</span><div><h2>Transaksi selesai!</h2><p class="sub">Total dan kembalianmu tepat. Skor +'+c.gain+'.</p></div></div>'+receiptHTML()+
    (chOn()?'':'<div class="actions">'+(c.src==='queue'?'<button class="btn" data-act="nextCust">'+(S.queue.length?'Panggil pembeli berikutnya':'Tunggu pembeli lagi')+'</button>':'<button class="btn" data-act="again">Belanja lagi</button>')+'<button class="btn ghost" data-act="go" data-v="dash">Ke beranda</button></div>');
}
function kasirHTML(){return chHudHTML()+kasirBody()}
function kasirBody(){
  var c=S.co;
  if(!c){
    return '<div class="card empty"><div class="big" aria-hidden="true">🧺</div><h1 class="ptitle">Belum ada belanjaan</h1><p class="sub">'+(chOn()?'Pembeli berikutnya sedang menuju kasir. Siapkan hitunganmu!':S.auto?'Belum ada pembeli yang antre. Tunggu sebentar, pembeli akan datang sendiri. Kamu juga bisa belanja sendiri di rak.':'Isi keranjang dulu di rak, lalu kembali ke kasir.')+'</p>'+(chOn()?'':'<button class="btn" data-act="go" data-v="shop">Ke rak belanja</button>')+'</div>';
  }
  var pct=Math.round(c.scanned/c.units.length*100);
  var en=S.enter;S.enter=null;
  return '<h1 class="ptitle">Kasir</h1><div class="steps" id="steps">'+stepsHTML()+'</div>'+
  '<div class="kasir-scene'+(en?' arriving':'')+'"'+(en&&en.wd?' style="--wd:'+en.wd+'s"':'')+'>'+
    '<div class="k-left">'+
      '<div class="k-row"><div class="k-cust'+(c.stage==='done'?' gone':(en?' walk':(c.stage==='scan'&&!c.scanned?' enter':'')))+'"'+(en?' style="--dx:'+en.dx+'px"':'')+'><div class="k-bubble">Tolong dihitung ya, Kak!</div><div class="k-face" aria-hidden="true">'+c.who.e+'</div><div class="k-name">'+esc(c.who.n)+'</div></div><div class="k-queue'+(en&&en.qdx>0?' walkq':'')+'" id="kq"'+(en&&en.qdx>0?' style="--qdx:'+en.qdx+'px"':'')+' aria-label="Pembeli yang menunggu giliran">'+queueStripHTML()+'</div></div>'+
      '<div class="konter" aria-label="Meja kasir supermarket">'+
        '<div class="konter-top">'+
          '<div class="belt-wrap"><div class="belt" id="belt">'+beltHTML(!!en)+'</div><div class="track" aria-hidden="true"></div></div>'+
          '<div class="scan-unit"><div class="laser" id="laser" aria-hidden="true"></div><span class="scan-label">SCANNER</span></div>'+
        '</div>'+
        '<div class="konter-body"><div class="counter"><span id="count">'+countText()+'</span><div class="bar"><i id="bar" style="width:'+pct+'%"></i></div></div><div class="konter-sign"><div class="ks-photo">'+fotoInner(safeFoto(S.profile.foto))+'</div><div class="ks-name">'+esc(S.profile.nama||'Kasir')+'</div></div></div>'+
      '</div>'+
    '</div>'+
    '<div class="k-right"><section class="monitor" aria-label="Layar komputer kasir"><div class="screen"><div class="scr-head"><span>PELANGI MART</span><span>'+esc(kasirNo())+'</span></div><ol class="scr-lines" id="lines">'+linesHTML()+'</ol><div class="scr-foot"><span>TOTAL</span><span>? ? ?</span></div></div></section></div>'+
  '</div><section class="panel" id="panel">'+panelHTML()+'</section>';
}
function focusAns(){var a=document.getElementById('ans');if(a)a.focus({preventScroll:true})}
function renderSteps(){var s=$('#steps');if(s)s.innerHTML=stepsHTML()}
function renderPanel(){var p=$('#panel');if(p)p.innerHTML=panelHTML()}

function scanUnit(u,el){
  var c=S.co;if(!c||c.stage!=='scan'||u.scanned)return;
  u.scanned=true;c.scanned++;c.lines.push(u);sfx.scan();
  if(el){el.classList.add('zap');setTimeout(function(){var b=$('#belt');if(b)b.innerHTML=beltHTML()},270)}
  var ol=$('#lines');
  if(ol){
    var hint=$('.scr-hint',ol);if(hint)hint.remove();
    ol.insertAdjacentHTML('beforeend',lineHTML(u,c.lines.length,true));ol.scrollTop=ol.scrollHeight;
  }
  var lz=$('#laser');if(lz){lz.classList.add('flash');setTimeout(function(){lz.classList.remove('flash')},140)}
  var ct=$('#count');if(ct)ct.textContent=countText();
  var bar=$('#bar');if(bar)bar.style.width=Math.round(c.scanned/c.units.length*100)+'%';
  if(c.scanned===c.units.length){
    c.stage='total';renderSteps();renderPanel();focusAns();
    setTimeout(function(){var b=$('#belt');if(b)b.innerHTML=beltHTML()},300);
  }
}
function scanNext(){
  var el=document.querySelector('.bitem:not(.zap)');
  if(el&&S.co)scanUnit(S.co.units[+el.dataset.u],el);
}

function checkTotal(){
  var c=S.co;if(!c||c.stage!=='total')return;
  var inp=$('#ans'),msg=$('#msg'),v=parseMoney(inp.value);
  if(v===null){msg.textContent='Tulis jawabanmu dulu ya.';shake(inp.parentElement);return}
  var st=S.stats;
  if(v===c.total){
    st.right++;st.streak++;st.best=Math.max(st.best,st.streak);st.score+=10;c.gain+=10;saveStats();chRight();
    c.stage='pay';c.paid=pickPaid(c.total);c.change=c.paid-c.total;c.say=pick(SAYS);
    sfx.ok();toast('Total tepat! Skor +10');renderSteps();renderPanel();focusAns();
  }else{
    st.wrong++;st.streak=0;saveStats();chWrong(true);sfx.bad();
    showModal('<h2 id="mt">Totalnya belum tepat</h2><p>Jawabanmu <b>'+rp(v)+'</b>. Total yang benar <b>'+rp(c.total)+'</b>.</p><p class="sub">'+(c.src==='queue'?'Pembeli ini pergi dengan kecewa dan antrean berlanjut. Coba cari tahu di mana hitunganmu meleset.':'Keranjang dikosongkan dan kita mulai belanja dari awal. Coba cari tahu di mana hitunganmu meleset.')+'</p><button class="btn" id="modalOk" data-act="restart">'+(c.src==='queue'?'Lanjut':'Mulai dari awal')+'</button>');
  }
}
function checkChange(){
  var c=S.co;if(!c||c.stage!=='pay')return;
  var inp=$('#ans'),msg=$('#msg'),v=parseMoney(inp.value);
  if(v===null){msg.textContent='Tulis jawabanmu dulu ya.';shake(inp.parentElement);return}
  var st=S.stats;
  if(v===c.change){
    st.right++;st.streak++;st.best=Math.max(st.best,st.streak);st.score+=10;st.done++;c.gain+=10;saveStats();chDone(c);
    c.stage='done';if(c.src==='queue'){S.queue=S.queue.filter(function(x){return x!==c.cust})}else{S.cart={};S.cartVer++}c.ver=S.cartVer;
    recordSale(c);sfx.ok();confetti();renderSteps();renderPanel();updateNav();
    var kc=$('.k-cust');if(kc){var kb=$('.k-bubble',kc);if(kb)kb.textContent='Terima kasih!';setTimeout(function(){
      if(!kc.isConnected)return;
      var sc=kc.closest('.kasir-scene'),r=kc.getBoundingClientRect(),l=sc?sc.getBoundingClientRect().left:0;
      kc.style.setProperty('--lx',-Math.ceil(r.right-l+12)+'px'); /* jarak pas sampai keluar layar, tidak lebih */
      /* walk/enter harus dilepas, kalau tidak animasinya menimpa 'leave' dan pelanggan cuma diam lalu hilang tiba-tiba */
      kc.classList.remove('walk','enter');kc.classList.add('leave');
      var fin=function(){if(S.co===c){c.left=true;advanceQueue()}};
      kc.addEventListener('animationend',function h(e){
        if(e.target!==kc||e.animationName!=='walkOut')return;
        kc.removeEventListener('animationend',h);fin();
      });
      setTimeout(fin,2200); /* cadangan kalau animationend tidak terpanggil */
    },450)}
    else setTimeout(function(){if(S.co===c){c.left=true;advanceQueue()}},1200);
  }else{
    st.wrong++;st.streak=0;saveStats();c.attempts++;chWrong(false);sfx.bad();
    msg.textContent='Belum tepat. Ingat: uang bayar dikurangi total. Coba hitung lagi.';
    shake(inp.parentElement);inp.select();
  }
}

/* ---------- admin ---------- */
function catOpts(sel){return CATS.map(function(c){return '<option'+(c.name===sel?' selected':'')+'>'+esc(c.name)+'</option>'}).join('')}
function adminList(){var q=S.aq.trim().toLowerCase(),cf=S.acat||'';return S.products.filter(function(p){return matchQ(p,q)&&(!cf||p.cat===cf)})}
function adminChipsInner(){
  var cf=S.acat||'';
  function chip(c,label,ic,n){return '<button type="button" class="cchip'+(cf===c?' on':'')+'" data-act="adminCat" data-c="'+esc(c)+'" aria-pressed="'+(cf===c?'true':'false')+'"><span aria-hidden="true">'+ic+'</span>'+esc(label)+'<small>'+n+'</small></button>'}
  return chip('','Semua rak','🌈',S.products.length)+CATS.map(function(c){
    return chip(c.name,c.name,c.ic,S.products.filter(function(p){return p.cat===c.name}).length)}).join('');
}
function adminRowsHTML(){
  var list=adminList();
  if(!list.length)return '<tr><td colspan="5" class="nores">'+(S.aq.trim()?'Tidak ada barang yang cocok dengan "'+esc(S.aq.trim())+'".':'Belum ada barang di rak ini.')+'</td></tr>';
  return list.map(function(p){
    return '<tr data-id="'+p.id+'"><td><input class="in emo" data-f="emoji" value="'+esc(p.emoji)+'" maxlength="8" aria-label="Gambar '+esc(p.name)+'"></td>'+
      '<td><input class="in" data-f="name" value="'+esc(p.name)+'" maxlength="24" aria-label="Nama barang"></td>'+
      '<td><input class="in mi" data-f="price" value="'+fmt(p.price)+'" inputmode="numeric" aria-label="Harga '+esc(p.name)+'"></td>'+
      '<td><select class="in" data-f="cat" aria-label="Rak '+esc(p.name)+'">'+catOpts(p.cat)+'</select></td>'+
      '<td><span class="rowact"><button class="btn ghost sm" data-act="edit" data-id="'+p.id+'" aria-label="Edit '+esc(p.name)+'">Edit</button><button class="btn ghost sm danger" data-act="del" data-id="'+p.id+'">Hapus</button></span></td></tr>';
  }).join('');
}
function adminCountText(){var f=adminList().length,n=S.products.length;return f===n?'('+n+')':'('+f+' dari '+n+')'}
function updateAdminList(){var b=$('#tbody');if(b)b.innerHTML=adminRowsHTML();var c=$('#acount');if(c)c.textContent=adminCountText()}
var EGROUPS=[
  {n:'Minuman',ic:'🥤',l:['🥛','🧃','🧋','💧','☕','🥤']},
  {n:'Camilan',ic:'🍿',l:['🍿','🍫','🍭','🍪','🍬','🍦','🍙']},
  {n:'Buah & Sayur',ic:'🥬',l:['🍎','🍌','🍓','🍊','🍇','🍑','🥕','🍉','🥦','🌽']},
  {n:'Seafood',ic:'🦐',l:['🦐','🦑','🦀','🦞','🐟','🐙','🦪','🐠']},
  {n:'Roti & Susu',ic:'🍞',l:['🍞','🥐','🧁','🧀','🥚','🍩','🍰','🥨']},
  {n:'Rumah Tangga',ic:'🧼',l:['🧼','🧻','🧴','🪥','🧽']}
];
function emojiPickerHTML(sel,act){
  sel=sel||S.pickE;act=act||'pickEmoji';
  var used={};
  var groups=EGROUPS.map(function(g){g.l.forEach(function(e){used[e]=1});return g});
  var rest=EMOJIS.filter(function(e){return !used[e]});
  if(rest.length)groups=groups.concat([{n:'Lainnya',ic:'✨',l:rest}]);
  if(sel&&!used[sel]&&rest.indexOf(sel)<0)groups=groups.concat([{n:'Saat ini',ic:'📌',l:[sel]}]);
  return groups.map(function(g){
    return '<div class="eg"><span class="eg-label"><span aria-hidden="true">'+g.ic+'</span>'+esc(g.n)+'</span><div class="eg-row">'+
      g.l.map(function(e){return '<button type="button" class="eb '+(e===sel?'on':'')+'" data-act="'+act+'" data-e="'+e+'" aria-label="Pilih gambar '+e+'" aria-pressed="'+(e===sel)+'">'+e+'</button>'}).join('')+'</div></div>';
  }).join('');
}
function updatePreview(){
  var f=$('#addForm');if(!f)return;
  var name=f.elements.pname.value.trim(),price=parseMoney(f.elements.pprice.value),cat=f.elements.pcat.value;
  var c=CATS.filter(function(x){return x.name===cat})[0]||CATS[0];
  var card=$('#npCard');if(card){card.style.setProperty('--back','var('+c.back+')');card.style.setProperty('--sign','var('+c.sign+')')}
  var el;
  if((el=$('#npPic')))el.textContent=S.pickE;
  if((el=$('#npName')))el.textContent=name||'Nama barang';
  if((el=$('#npPrice')))el.textContent=price===null?'Rp -':rp(price);
  if((el=$('#npRak')))el.textContent='Rak '+c.name;
}
function adminHTML(){
  var c0=CATS[0];
  return '<div class="admin-top"><div><h1 class="ptitle">Kelola barang</h1><p class="sub">Tambah barang baru, ubah nama dan harga. Perubahan langsung muncul di rak dan tersimpan di browser ini.</p></div></div>'+
  '<div class="admin">'+
    '<section class="card np"><header class="np-head"><span class="np-badge" aria-hidden="true">＋</span><div><h2>Tambah barang</h2><p class="sub">Isi datanya, pilih gambar, lalu lihat pratinjaunya di sebelah kanan.</p></div></header>'+
      '<form data-form="add" id="addForm" autocomplete="off"><div class="np-body">'+
        '<div class="np-main">'+
          '<div class="np-step"><span class="np-num">1</span>Data barang</div>'+
          '<div class="np-fields">'+
            '<label class="fld np-name"><span>Nama barang</span><input class="in" name="pname" maxlength="24" placeholder="Contoh: Yogurt Peach"></label>'+
            '<label class="fld"><span>Harga (Rp)</span><input class="in mi" name="pprice" inputmode="numeric" placeholder="Contoh: 7.500"></label>'+
            '<label class="fld"><span>Rak</span><select class="in" name="pcat">'+catOpts('')+'</select></label>'+
          '</div>'+
          '<div class="np-step"><span class="np-num">2</span>Pilih gambar</div>'+
          '<div class="emoji-picker" id="emojiGrid">'+emojiPickerHTML()+'</div>'+
        '</div>'+
        '<aside class="np-side" aria-label="Pratinjau barang"><span class="np-label">Pratinjau di rak</span>'+
          '<div class="np-card" id="npCard" style="--back:var('+c0.back+');--sign:var('+c0.sign+')"><span class="np-pic" id="npPic" aria-hidden="true">'+esc(S.pickE)+'</span><b id="npName">Nama barang</b><i id="npPrice">Rp -</i></div>'+
          '<span class="np-rak" id="npRak">Rak '+esc(c0.name)+'</span>'+
          '<div class="np-act"><button class="btn mint" type="submit">Tambah ke rak</button><span class="msg" id="addmsg" role="status"></span></div>'+
        '</aside>'+
      '</div></form></section>'+
    '<section class="card"><div class="admin-top"><h2>Daftar barang <span id="acount">'+adminCountText()+'</span></h2><button class="btn ghost sm danger" data-act="reset">Kembalikan barang awal</button></div>'+
      (S.products.length?searchBox('adminSearch',S.aq,'Cari nama barang atau rak...')+'<div class="cchips" id="adminChips" role="group" aria-label="Saring berdasarkan rak">'+adminChipsInner()+'</div><div class="tbl-wrap"><table class="tbl"><thead><tr><th>Gambar</th><th>Nama</th><th>Harga (Rp)</th><th>Rak</th><th></th></tr></thead><tbody id="tbody">'+adminRowsHTML()+'</tbody></table></div>':'<p class="sub">Belum ada barang. Tambahkan lewat formulir di atas.</p>')+
    '</section></div>';
}
function openEdit(id){
  var p=byId(id);if(!p)return;
  S.editId=id;S.editE=p.emoji;
  var c=CATS.filter(function(x){return x.name===p.cat})[0]||CATS[0];
  showModal(
    '<div class="ed-head"><div class="np-card ed-card" id="edCard" style="--back:var('+c.back+');--sign:var('+c.sign+')"><span class="np-pic" id="edPic" aria-hidden="true">'+esc(p.emoji)+'</span><b id="edName">'+esc(p.name)+'</b><i id="edPrice">'+rp(p.price)+'</i></div>'+
      '<div><h2 id="mt">Edit barang</h2><p class="sub">Ubah nama, harga, rak, atau gambar. Pratinjaunya langsung berubah.</p></div></div>'+
    '<form class="ed-body" data-form="edit" id="editForm" autocomplete="off">'+
      '<div class="np-fields">'+
        '<label class="fld np-name"><span>Nama barang</span><input class="in" name="ename" maxlength="24" value="'+esc(p.name)+'"></label>'+
        '<label class="fld"><span>Harga (Rp)</span><input class="in mi" name="eprice" inputmode="numeric" value="'+fmt(p.price)+'"></label>'+
        '<label class="fld"><span>Rak</span><select class="in" name="ecat">'+catOpts(p.cat)+'</select></label>'+
      '</div>'+
      '<div class="fld"><span>Gambar</span></div>'+
      '<div class="emoji-picker" id="editEmojiGrid">'+emojiPickerHTML(p.emoji,'editEmoji')+'</div>'+
      '<div class="ed-act"><button class="btn mint" type="submit">Simpan perubahan</button><button class="btn ghost" type="button" data-act="editClose">Batal</button><span class="msg" id="editmsg" role="status"></span></div>'+
    '</form>');
  var m=$('#modalRoot .modal');if(m)m.classList.add('ed');
  var n=$('#editForm').elements.ename;n.focus();n.select();
}
function updateEditPreview(){
  var f=$('#editForm');if(!f)return;
  var name=f.elements.ename.value.trim(),price=parseMoney(f.elements.eprice.value),cat=f.elements.ecat.value;
  var c=CATS.filter(function(x){return x.name===cat})[0]||CATS[0];
  var card=$('#edCard');if(card){card.style.setProperty('--back','var('+c.back+')');card.style.setProperty('--sign','var('+c.sign+')')}
  var el;
  if((el=$('#edPic')))el.textContent=S.editE;
  if((el=$('#edName')))el.textContent=name||'Nama barang';
  if((el=$('#edPrice')))el.textContent=price===null?'Rp -':rp(price);
}
function saveEdit(form){
  var p=byId(S.editId),msg=$('#editmsg');if(!p)return;
  var name=form.elements.ename.value.trim().slice(0,24),price=parseMoney(form.elements.eprice.value),cat=form.elements.ecat.value;
  if(!name){msg.textContent='Nama barang tidak boleh kosong.';form.elements.ename.focus();return}
  if(price===null||price<100){msg.textContent='Harga minimal Rp 100.';form.elements.eprice.focus();return}
  if(form.dataset.busy)return;
  form.dataset.busy='1';msg.textContent='Menyimpan...';
  api({action:'update',id:Number(p.id),name:name,price:price,cat:cat,emoji:S.editE}).then(function(){
    p.name=name;p.price=price;p.cat=cat;p.emoji=S.editE;S.editId=null;saveProducts();
    closeModal();updateAdminList();
    var ch=$('#adminChips');if(ch)ch.innerHTML=adminChipsInner();
    toast(name+' berhasil diubah');
  }).catch(function(e){delete form.dataset.busy;msg.textContent='';apiFail(e)});
}
function addProduct(form){
  var name=form.elements.pname.value.trim(),price=parseMoney(form.elements.pprice.value),cat=form.elements.pcat.value,msg=$('#addmsg');
  if(!name){msg.textContent='Isi nama barangnya dulu.';form.elements.pname.focus();return}
  if(price===null||price<100){msg.textContent='Harga minimal Rp 100.';form.elements.pprice.focus();return}
  if(form.dataset.busy)return;
  form.dataset.busy='1';msg.textContent='Menyimpan...';
  api({action:'add',name:name.slice(0,24),price:price,cat:cat,emoji:S.pickE}).then(function(j){
    S.products.push(norm(j.produk));S.aq='';saveProducts();render();toast(name+' masuk ke Rak '+cat);
  }).catch(function(e){
    delete form.dataset.busy;msg.textContent='';apiFail(e);
  });
}


/* ---------- isi awal Rak Seafood ---------- */
var SEAFOOD=[
  {name:'Udang Segar',price:25000,emoji:'🦐'},
  {name:'Cumi-cumi',price:28000,emoji:'🦑'},
  {name:'Kepiting',price:45000,emoji:'🦀'},
  {name:'Lobster',price:85000,emoji:'🦞'},
  {name:'Ikan Kembung',price:18000,emoji:'🐟'},
  {name:'Ikan Salmon',price:42000,emoji:'🐠'},
  {name:'Gurita',price:38000,emoji:'🐙'},
  {name:'Kerang',price:15000,emoji:'🦪'}
];
var seeding=false;
function seedSeafood(){
  if(seeding||LS.get('pm_seed_seafood',false))return;
  if(S.products.some(function(p){return p.cat==='Seafood'})){LS.set('pm_seed_seafood',true);return}
  seeding=true;
  var i=0;
  (function next(){
    if(i>=SEAFOOD.length){seeding=false;LS.set('pm_seed_seafood',true);return}
    var it=SEAFOOD[i++];
    api({action:'add',name:it.name,price:it.price,cat:'Seafood',emoji:it.emoji}).then(function(j){
      S.products.push(norm(j.produk));saveProducts();
      if(!chOn()&&S.view==='shop')render();
      else if(S.view==='admin'){updateAdminList();var ch=$('#adminChips');if(ch)ch.innerHTML=adminChipsInner()}
      next();
    }).catch(function(){seeding=false;if(window.console)console.warn('Rak Seafood belum bisa disimpan ke database (api.php).')});
  })();
}

/* ---------- render ---------- */
var app=document.getElementById('app');
function render(){
  var v=S.view;
  if(v==='title'){app.innerHTML=titleHTML();var b=$('#playBtn');if(b)b.focus({preventScroll:true});return}
  var main=v==='dash'?dashHTML():v==='shop'?shopHTML():v==='kasir'?kasirHTML():v==='tantang'?tantangHTML():v==='profil'?profilHTML():adminHTML();
  lastNav=navHTML();
  app.innerHTML='<div id="navwrap">'+lastNav+'</div><main class="page">'+main+'</main>';
  if(v==='kasir')focusAns();
  requestAnimationFrame(shelfArrows);
}
function go(v){
  if(chOn()&&v!=='kasir'){toast('Tantangan sedang berjalan. Tekan Berhenti kalau mau keluar.');return}
  if(v==='kasir')startCheckout();
  if(v!==S.view){S.q='';S.aq='';S.cat='';S.acat=''}
  S.view=v;render();window.scrollTo(0,0);
}

/* ---------- aksi ---------- */
/* ---------- mode tantangan ---------- */
/* CSS khusus mode tantangan dipasang dari JS, jadi tidak bergantung pada stylesheet utama */

function chOn(){return !!(S.ch&&S.ch.running)}
function fmtTime(s){var m=Math.floor(s/60),r=s%60;return m+':'+(r<10?'0':'')+r}
function durLabel(d){return (d/60)+' menit'}
function chRight(){var ch=S.ch;if(!ch||!ch.running)return;ch.right++;ch.score+=10;ch.streak++;ch.best=Math.max(ch.best,ch.streak);chHudUpdate()}
function chWrong(lost){var ch=S.ch;if(!ch||!ch.running)return;ch.wrong++;ch.streak=0;if(lost)ch.lost++;chHudUpdate()}
function chDone(c){
  var ch=S.ch;if(!ch||!ch.running)return;
  chRight();ch.served++;ch.items+=c.units.length;ch.times.push(Date.now()-c.t0);chHudUpdate();
}
function chHudHTML(){
  var ch=S.ch;if(!ch||!ch.running)return '';
  var rem=Math.max(0,Math.ceil((ch.endAt-Date.now())/1000)),tot=ch.right+ch.wrong;
  function card(cls,ic,id,l,v){return '<div class="ch-st '+cls+'"><span class="ch-ic" aria-hidden="true">'+ic+'</span><div><b id="'+id+'">'+v+'</b><small>'+l+'</small></div></div>'}
  var pct=Math.max(0,(ch.endAt-Date.now())/(ch.dur*1000)*100).toFixed(1);
  return '<div class="ch-hud'+(rem<=10?' low':'')+'" id="chHud" role="group" aria-label="Status tantangan">'+
    '<div class="ch-ring" id="chRing" style="--p:'+pct+'"><div class="ch-ring-in"><b id="chTime">'+fmtTime(rem)+'</b><small>SISA WAKTU</small></div></div>'+
    '<div class="ch-cards">'+card('a','🛒','chServed','Dilayani',ch.served)+card('b','🎯','chAcc','Ketepatan',tot?Math.round(ch.right/tot*100)+'%':'-')+card('c','🔥','chStreak','Beruntun',ch.streak)+card('d','⭐','chScore','Skor',ch.score)+'</div>'+
    '<button class="btn ghost sm danger" data-act="chStop">Berhenti</button></div>';
}
function chHudUpdate(){
  var ch=S.ch;if(!ch||!ch.running)return;
  var rem=Math.max(0,Math.ceil((ch.endAt-Date.now())/1000)),tot=ch.right+ch.wrong;
  function set(id,v,bump){
    var e=document.getElementById(id);if(!e||e.textContent===String(v))return;
    e.textContent=v;
    if(bump){var c=e.closest('.ch-st');if(c){c.classList.remove('bump');void c.offsetWidth;c.classList.add('bump')}}
  }
  set('chTime',fmtTime(rem));set('chServed',ch.served,1);set('chAcc',tot?Math.round(ch.right/tot*100)+'%':'-');set('chStreak',ch.streak,1);set('chScore',ch.score,1);
  var rg=document.getElementById('chRing');if(rg)rg.style.setProperty('--p',Math.max(0,(ch.endAt-Date.now())/(ch.dur*1000)*100).toFixed(1));
  var h=document.getElementById('chHud');if(h)h.classList.toggle('low',rem<=10);
}
function chTick(){
  var ch=S.ch;if(!ch||!ch.running)return;
  var now=Date.now();
  if(now>=ch.endAt){finishChallenge(false);return}
  chHudUpdate();
  var rem=Math.ceil((ch.endAt-now)/1000);
  if(rem<=5&&rem!==ch.lastRem){ch.lastRem=rem;sfx.cd(false)}
  if(S.view!=='kasir')return;
  var idle=!S.queue.length&&(!S.co||S.co.stage==='done');
  if(S.queue.length<3&&now-ch.lastSpawn>=(idle?1200:ch.gap)){ch.lastSpawn=now;ch.gap=rnd(4500,8000);spawnCustomer()}
}
function beginChallenge(dur){
  var now=Date.now();
  S.ch={dur:dur,running:true,endAt:now+dur*1000,served:0,right:0,wrong:0,lost:0,streak:0,best:0,score:0,items:0,times:[],lastSpawn:now,gap:5000,lastRem:0,stash:{cart:S.cart,queue:S.queue}};
  S.cart={};S.queue=[];S.co=null;S.cartVer++;
  go('kasir');
  spawnCustomer();spawnCustomer();
}
function chStars(r){
  if(r.acc>=90&&r.served>=Math.ceil(r.dur/25))return 3;
  if(r.acc>=70&&r.served>=Math.ceil(r.dur/40))return 2;
  return r.served>=1?1:0;
}
function chTarget(d){return {s3:Math.ceil(d/25),s2:Math.ceil(d/40)}}
function chEval(r,stars,prev,isRec){
  var out=[],tot=r.right+r.wrong,chg=r.wrong-r.lost,t=chTarget(r.dur);
  if(!tot)out.push(['💡','Belum ada jawaban yang masuk. Selesaikan satu pembeli dulu supaya hitunganmu bisa dievaluasi.']);
  else if(r.acc>=90)out.push(['✅','Ketelitianmu sangat baik: '+r.acc+'% jawaban benar.']);
  else if(r.acc>=70)out.push(['👍','Cukup teliti ('+r.acc+'%). Kurangi salah hitung supaya bintangmu naik.']);
  else out.push(['⚠️','Ketepatan baru '+r.acc+'%. Pelan sedikit, lebih baik teliti daripada cepat.']);
  if(r.lost>0&&r.lost>=chg)out.push(['🧮',r.lost+' kali salah di langkah total. Jumlahkan harga satu per satu dan cek ulang sebelum menekan Periksa.']);
  else if(chg>0)out.push(['💸',chg+' kali salah di kembalian. Coba hitung naik bertahap seperti di Tips hitung cepat.']);
  else if(tot>0)out.push(['🌟','Tidak ada salah hitung sama sekali. Mantap!']);
  if(r.served>0){
    if(r.avg<=15)out.push(['⚡','Cepat! Rata-rata '+r.avg+' detik per pembeli.']);
    else if(r.avg<=35)out.push(['⏱️','Kecepatan lumayan: rata-rata '+r.avg+' detik per pembeli.']);
    else out.push(['🐢','Rata-rata '+r.avg+' detik per pembeli. Coba lebih cepat men-scan dan menjumlahkan.']);
  }
  if(stars===0)out.push(['🎯','Untuk bintang pertama: selesaikan minimal 1 pembeli sampai kembaliannya benar.']);
  else if(stars<3){var n=stars===2?t.s3:t.s2,a=stars===2?90:70;out.push(['🎯','Untuk bintang '+(stars+1)+': layani minimal '+n+' pembeli dengan ketepatan '+a+'% atau lebih.'])}
  else out.push(['🏆','Bintang penuh! Coba durasi yang lebih panjang untuk tantangan baru.']);
  if(prev&&!isRec&&r.score<prev.score)out.push(['📈','Rekormu untuk '+durLabel(r.dur)+' adalah '+prev.score+' poin. Kurang '+(prev.score-r.score)+' poin lagi.']);
  return out.slice(0,4);
}
function chResultHTML(r,isRec,stars,manual,prev){
  var emo=['🌱','🌟','🎉','🏆'][stars],grade=['Belum ada yang dilayani','Awal yang bagus!','Kasir cekatan!','Kasir super!'][stars];
  var st='';for(var i=0;i<3;i++)st+='<span class="'+(i<stars?'on':'')+'" style="--i:'+i+'">★</span>';
  function tile(i,l,v){return '<div class="res-tile"><div class="i" aria-hidden="true">'+i+'</div><b>'+v+'</b><small>'+l+'</small></div>'}
  var delta=prev?(r.score>=prev.score?'Rekor lama: '+prev.score:'Rekormu: '+prev.score):'Belum ada rekor sebelumnya';
  return '<div class="res-head s'+stars+'"><div class="res-emoji" aria-hidden="true">'+emo+'</div><h2 id="mt">'+(manual?'Tantangan selesai!':'Waktu habis!')+'</h2><p class="res-grade">'+grade+'</p>'+
      '<div class="res-stars" role="img" aria-label="'+stars+' dari 3 bintang">'+st+'</div>'+(isRec?'<span class="ch-new">🎉 Rekor baru!</span>':'')+'</div>'+
    '<div class="res-body">'+
      '<div class="res-top"><div class="res-score"><small>SKOR</small><b id="resScore">0</b><span class="res-delta">'+delta+'</span></div>'+
        '<div class="res-acc"><div class="res-ring" style="--p:'+r.acc+'" role="img" aria-label="Ketepatan '+r.acc+' persen"><div><b>'+r.acc+'%</b><small>KETEPATAN</small></div></div><div class="res-bs"><span class="ok">✓ <b>'+r.right+'</b> benar</span><span class="no">✕ <b>'+r.wrong+'</b> salah</span></div></div></div>'+
      '<div class="res-tiles">'+tile('🛒','Dilayani',r.served)+tile('😢','Pembeli kecewa',r.lost)+tile('🔥','Beruntun terbaik',r.best)+tile('⏱️','Rata-rata',r.avg?r.avg+' dtk':'-')+tile('📦','Barang terjual',r.items)+tile('🎯','Jawaban benar',r.right)+'</div>'+
      '<div class="res-eval"><h3>Evaluasi</h3><ul>'+chEval(r,stars,prev,isRec).map(function(e){return '<li><i aria-hidden="true">'+e[0]+'</i><span>'+e[1]+'</span></li>'}).join('')+'</ul></div>'+
      '<div class="res-act"><button class="btn mint" data-act="chAgain">Main lagi</button><button class="btn ghost" data-act="chClose">Ganti durasi</button><button class="btn ghost" data-act="chHome">Ke beranda</button></div>'+
    '</div>';
}
function showResult(r,isRec,stars,manual,prev){
  showModal(chResultHTML(r,isRec,stars,manual,prev));
  var m=$('#modalRoot .modal');
  if(m){m.classList.add('res');m.setAttribute('tabindex','-1');m.focus({preventScroll:true})}
  /* tombol dikunci sebentar supaya Enter yang masih ditekan tidak langsung memulai ulang */
  var bs=$$('#modalRoot .res-act .btn');bs.forEach(function(b){b.disabled=true});
  setTimeout(function(){bs.forEach(function(b){b.disabled=false})},1500);
  var el=$('#resScore'),reduce=window.matchMedia&&matchMedia('(prefers-reduced-motion: reduce)').matches;
  if(!el)return;
  if(reduce||!r.score){el.textContent=r.score;return}
  var t0=performance.now(),D=900;
  (function f(n){var k=Math.min(1,(n-t0)/D);el.textContent=Math.round(r.score*(1-Math.pow(1-k,3)));if(k<1&&el.isConnected)requestAnimationFrame(f);else el.textContent=r.score})(t0);
}
function finishChallenge(manual){
  var ch=S.ch;if(!ch||!ch.running)return;
  ch.running=false;
  var tot=ch.right+ch.wrong,sum=ch.times.reduce(function(a,b){return a+b},0);
  var r={t:Date.now(),dur:ch.dur,score:ch.score,served:ch.served,acc:tot?Math.round(ch.right/tot*100):0,right:ch.right,wrong:ch.wrong,lost:ch.lost,best:ch.best,avg:ch.times.length?Math.round(sum/ch.times.length/1000):0,items:ch.items};
  S.cart=ch.stash.cart;S.queue=ch.stash.queue;S.co=null;S.cartVer++;S.ch=null;
  var D=S.chData,prev=D.best[r.dur],isRec=r.score>0&&(!prev||r.score>prev.score),stars=chStars(r);
  if(isRec)D.best[r.dur]=r;
  D.runs=(D.runs||[]).concat([r]).slice(-10);LS.set('pm_ch',D);
  go('tantang');
  showResult(r,isRec,stars,manual,prev);
  if(stars>=2){sfx.ok();confetti()}else sfx.bad();
}
function startChallenge(dur){
  if(S.ch||S.counting||S.loading)return;
  if(!S.products.length){toast('Tambahkan barang dulu di menu Kelola Barang.');return}
  S.counting=true;
  var reduce=window.matchMedia&&matchMedia('(prefers-reduced-motion: reduce)').matches;
  var el=document.createElement('div');
  el.className='loader cd';el.setAttribute('role','status');el.setAttribute('aria-live','assertive');
  el.style.setProperty('--step',(reduce?.4:.9)+'s');
  el.innerHTML=
    '<span class="float" style="left:8%;top:12%;--d:0s" aria-hidden="true">🍓</span>'+
    '<span class="float" style="right:9%;top:16%;--d:.6s" aria-hidden="true">🧁</span>'+
    '<span class="float" style="left:12%;bottom:14%;--d:1.1s" aria-hidden="true">🍭</span>'+
    '<span class="float" style="right:10%;bottom:12%;--d:.3s" aria-hidden="true">🥛</span>'+
    '<div class="cd-wrap"><p class="cd-chip"><span aria-hidden="true">⏱️</span> Mode Tantangan · '+durLabel(dur)+'</p>'+
      '<div class="cd-dial" id="cdDial"><span class="cd-ripple" id="cdRip" aria-hidden="true"></span>'+
        '<svg class="cd-svg" viewBox="0 0 200 200" aria-hidden="true"><circle class="cd-track" cx="100" cy="100" r="88"/><circle class="cd-arc" id="cdArc" cx="100" cy="100" r="88"/></svg>'+
        '<div class="cd-core"><span class="cd-num" id="cdNum">?</span></div></div>'+
      '<p class="cd-msg" id="cdMsg">Siap-siap, kasir!</p>'+
      '<div class="cd-queue" id="cdQ" aria-hidden="true"><span>🐱</span><span>🐻</span><span>🐰</span></div></div>';
  document.body.appendChild(el);
  requestAnimationFrame(function(){requestAnimationFrame(function(){el.classList.add('in')})});
  var seq=[
    {t:'3',c:'var(--pink)',s:'var(--pink-deep)',m:'Tarik napas dulu...'},
    {t:'2',c:'var(--butter)',s:'var(--butter-deep)',m:'Fokus ke hitunganmu!'},
    {t:'1',c:'var(--mint)',s:'var(--mint-deep)',m:'Pembeli mulai antre!'},
    {t:'Mulai!',c:'var(--sky)',s:'var(--sky-deep)',m:'Ayo layani semuanya!'}
  ],i=0;
  function retrig(n,c){n.classList.remove(c);void n.offsetWidth;n.classList.add(c)}
  function step(){
    var x=seq[i],last=i===seq.length-1,dial=$('#cdDial',el),num=$('#cdNum',el),msg=$('#cdMsg',el),q=$('#cdQ',el);
    dial.style.setProperty('--c',x.c);dial.style.setProperty('--sh',x.s);
    num.textContent=x.t;num.classList.toggle('go',last);retrig(num,'pop');
    retrig($('#cdArc',el),'run');retrig($('#cdRip',el),'go');
    $$('span',q).forEach(function(sp,k){sp.classList.toggle('on',k<=i)});
    q.classList.toggle('rush',last);
    if(msg)msg.textContent=x.m;
    sfx.cd(last,i);
    if(last)confetti();
    i++;
    if(i<seq.length)setTimeout(step,reduce?500:900);
    else setTimeout(function(){
      beginChallenge(dur);
      el.classList.remove('in');
      setTimeout(function(){el.remove();S.counting=false},350);
    },reduce?500:750);
  }
  setTimeout(step,reduce?200:450);
}
var CH_DURS=[{d:60,ic:'⚡',n:'Kilat'},{d:120,ic:'🎯',n:'Seimbang'},{d:180,ic:'🏃',n:'Maraton'}];
function tantangHTML(){
  var D=S.chData,d=S.chDur,t=chTarget(d);
  var opts=CH_DURS.map(function(x){return '<button class="ch-opt'+(x.d===d?' on':'')+'" role="radio" aria-checked="'+(x.d===d)+'" data-act="chDur" data-d="'+x.d+'"><span class="ic" aria-hidden="true">'+x.ic+'</span><b>'+durLabel(x.d)+'</b><small>'+x.n+'</small></button>'}).join('');
  var recs=CH_DURS.map(function(x){var b=D.best[x.d];
    return '<li class="ch-rc d'+x.d+(b?'':' none')+'"><span class="medal" aria-hidden="true">🏅</span><div><span class="t">'+durLabel(x.d)+'</span><small>'+(b?b.served+' pembeli · ketepatan '+b.acc+'%':'Belum ada rekor')+'</small></div>'+(b?'<div class="sc">'+b.score+'<small> poin</small></div>':'')+'</li>'}).join('');
  var runs=(D.runs||[]).slice(-5).reverse();
  var list=runs.length?'<ul class="ch-runs">'+runs.map(function(x){
    var n=chStars(x),st='';for(var i=0;i<3;i++)st+='<i class="'+(i<n?'':'off')+'">★</i>';
    var w=new Date(x.t).toLocaleString('id-ID',{day:'2-digit',month:'short',hour:'2-digit',minute:'2-digit'});
    return '<li class="ch-run"><span class="st" aria-label="'+n+' dari 3 bintang">'+st+'</span><div><b>'+durLabel(x.dur)+' · '+x.served+' dilayani</b><small>Ketepatan '+x.acc+'% · '+esc(w)+'</small></div><span class="pt">'+x.score+'</span></li>'}).join('')+'</ul>':dEmpty('Belum ada permainan. Mulai tantangan pertamamu!');
  return '<section class="ch-hero"><div><span class="ch-eyebrow">Adu cepat dan teliti</span><h1 class="ptitle">Mode Tantangan</h1><p class="sub">Layani sebanyak mungkin pembeli sebelum waktu habis. Makin cepat dan teliti hitunganmu, makin tinggi skornya.</p></div><div class="ch-art" aria-hidden="true"><span>🛒</span><span>⏱️</span><span>🏆</span></div></section>'+
  '<div class="ch-grid">'+
    '<section class="card"><h2>Pilih durasi</h2><div class="ch-opts" role="radiogroup" aria-label="Durasi tantangan">'+opts+'</div>'+
      '<p class="ch-target">⭐ Target bintang 3: layani minimal <b>'+t.s3+' pembeli</b> dengan ketepatan <b>90%</b> atau lebih.</p>'+
      '<h2>Cara main</h2><ol class="ch-steps"><li>Scan semua barang di meja kasir.</li><li>Hitung total belanjaan. Kalau salah, pembeli kecewa dan pergi.</li><li>Hitung kembalian. Boleh dicoba lagi, tapi dihitung sebagai jawaban salah.</li><li>Setiap jawaban benar dapat +10 poin. Keranjangmu aman dan kembali seperti semula.</li></ol>'+
      '<button class="btn play ch-go" data-act="chStart">Mulai tantangan</button></section>'+
    '<section class="card"><h2>Rekor terbaikmu</h2><ul class="ch-recs">'+recs+'</ul><h2 class="ch-sub">Permainan terakhir</h2>'+list+'</section>'+
  '</div>';
}
function startGame(){
  if(S.loading)return;S.loading=true;
  var reduce=window.matchMedia&&matchMedia('(prefers-reduced-motion: reduce)').matches,dur=reduce?900:2600;
  var el=document.createElement('div');
  el.className='loader';el.setAttribute('role','status');el.setAttribute('aria-live','polite');
  el.style.setProperty('--ld',dur+'ms');
  el.innerHTML=
    '<span class="float" style="left:10%;top:14%;--d:0s" aria-hidden="true">🍓</span>'+
    '<span class="float" style="right:12%;top:18%;--d:.7s" aria-hidden="true">🧁</span>'+
    '<span class="float" style="left:14%;bottom:16%;--d:1.2s" aria-hidden="true">🍭</span>'+
    '<span class="float" style="right:10%;bottom:14%;--d:.4s" aria-hidden="true">🥛</span>'+
    '<div class="ld-card">'+
      '<div class="ld-rainbow">'+LOGO+'</div>'+
      '<div class="ld-stage" aria-hidden="true">'+
        '<span class="ld-drop" style="--x:-34px;--d:0s">🍓</span>'+
        '<span class="ld-drop" style="--x:4px;--d:.4s">🥛</span>'+
        '<span class="ld-drop" style="--x:36px;--d:.8s">🍞</span>'+
        '<span class="ld-cart">🛒</span>'+
      '</div>'+
      '<h2 class="ld-title">Pelangi<span>Mart</span></h2>'+
      '<p class="ld-msg">Menata rak belanja...</p>'+
      '<div class="ld-bar" aria-hidden="true"><i></i></div>'+
    '</div>';
  document.body.appendChild(el);
  requestAnimationFrame(function(){requestAnimationFrame(function(){el.classList.add('in')})});
  sfx.add();
  var msgs=['Menata rak belanja...','Mengisi keranjang...','Menyalakan mesin kasir...','Toko siap dibuka!'];
  msgs.forEach(function(m,i){
    if(!i)return;
    setTimeout(function(){var t=el.querySelector('.ld-msg');if(t)t.textContent=m;if(i<msgs.length-1)sfx.scan()},dur*i/msgs.length);
  });
  setTimeout(function(){
    sfx.ok();S.view='dash';render();window.scrollTo(0,0);
    el.classList.remove('in');
    setTimeout(function(){el.remove();S.loading=false},350);
  },dur);
}
var A={
  play:startGame,
  title:function(){if(chOn()){toast('Tantangan sedang berjalan. Tekan Berhenti kalau mau keluar.');return}S.view='title';render()},
  chStart:function(){startChallenge(S.chDur)},
  chDur:function(t){S.chDur=+t.dataset.d;LS.set('pm_chdur',S.chDur);render()},
  chStop:function(t){
    if(t.dataset.sure){finishChallenge(true);return}
    t.dataset.sure='1';t.textContent='Yakin berhenti?';
    setTimeout(function(){if(t.isConnected){delete t.dataset.sure;t.textContent='Berhenti'}},3000);
  },
  chAgain:function(){closeModal();startChallenge(S.chDur)},
  chHome:function(){closeModal();go('dash')},
  chClose:function(){closeModal()},
  go:function(t){if(t.dataset.v===S.view&&S.view==='kasir')return;go(t.dataset.v)},
  edit:function(t){openEdit(t.dataset.id)},
  editEmoji:function(t){
    S.editE=t.dataset.e;
    $$('#editEmojiGrid .eb').forEach(function(b){var on=b.dataset.e===S.editE;b.classList.toggle('on',on);b.setAttribute('aria-pressed',on)});
    updateEditPreview();
  },
  editClose:function(){S.editId=null;closeModal()},
  adminCat:function(t){S.acat=t.dataset.c||'';var c=$('#adminChips');if(c)c.innerHTML=adminChipsInner();updateAdminList()},
  zoomFoto:function(t){var src=S.pickFoto||safeFoto(S.profile.foto);if(src)openLightbox(src,t)},
  shelfL:function(t){var r=t.closest('.shelf').querySelector('.shelf-row');r.scrollBy({left:-r.clientWidth*.8,behavior:'smooth'})},
  shelfR:function(t){var r=t.closest('.shelf').querySelector('.shelf-row');r.scrollBy({left:r.clientWidth*.8,behavior:'smooth'})},
  shopCat:function(t){S.cat=t.dataset.c||'';render()},
  tokasir:function(){go('kasir')},
  clearSearch:function(t){var i=t.parentElement.querySelector('.sinput');i.value='';i.dispatchEvent(new Event('input',{bubbles:true}));i.focus()},
  sound:function(){S.sound=!S.sound;LS.set('pm_sound',S.sound);updateNav()},
  tip:function(){S.tip=(S.tip+1)%TIPS.length;var t=$('#tipText');if(t)t.textContent=TIPS[S.tip]},
  add:function(t){
    var id=t.dataset.id;if(!byId(id))return;
    if((S.cart[id]||0)>=9){toast('Maksimal 9 untuk satu barang.');return}
    S.cart[id]=(S.cart[id]||0)+1;S.cartVer++;sfx.add();
    var pic=$('.prod-pic',t);if(pic){pic.classList.remove('pop');void pic.offsetWidth;pic.classList.add('pop')}
    refreshShop();
  },
  inc:function(t){var id=t.dataset.id;if((S.cart[id]||0)>=9){toast('Maksimal 9 untuk satu barang.');return}S.cart[id]=(S.cart[id]||0)+1;S.cartVer++;refreshShop()},
  dec:function(t){var id=t.dataset.id;S.cart[id]=(S.cart[id]||0)-1;if(S.cart[id]<=0)delete S.cart[id];S.cartVer++;refreshShop()},
  rm:function(t){delete S.cart[t.dataset.id];S.cartVer++;refreshShop()},
  clear:function(){S.cart={};S.cartVer++;refreshShop()},
  scan:function(t){if(S.co)scanUnit(S.co.units[+t.dataset.u],t)},
  scanNext:scanNext,
  restart:function(){
    if(S.co&&S.co.src==='queue'){var cu=S.co.cust;S.queue=S.queue.filter(function(x){return x!==cu});S.co=null;closeModal();go('kasir');return}
    S.cart={};S.cartVer++;S.co=null;closeModal();go('shop')
  },
  cropOk:applyCrop,
  cropCancel:function(){CROP=null;closeModal();var m=$('#pfmsg');if(m)m.textContent=''},
  clearFoto:function(){S.pickFoto='';updateFotoPrev();var m=$('#pfmsg');if(m)m.textContent='Foto dihapus. Klik Simpan profil untuk menyimpannya.'},
  nextCust:function(){
    var c=S.co;
    if(c&&c.stage==='done'&&S.view==='kasir'&&S.queue.length&&!cartCount()){c.left=true;advanceQueue();return}
    S.co=null;go('kasir');
  },
  auto:function(){
    S.auto=!S.auto;LS.set('pm_auto',S.auto);S.nextIn=3;render();
    toast(S.auto?'Mode belanja otomatis menyala':'Mode belanja otomatis mati');
  },
  again:function(){S.co=null;go('shop')},
  pickEmoji:function(t){
    S.pickE=t.dataset.e;
    $$('#emojiGrid .eb').forEach(function(b){var on=b.dataset.e===S.pickE;b.classList.toggle('on',on);b.setAttribute('aria-pressed',on)});
    updatePreview();
  },
  del:function(t){
    if(t.dataset.sure){
      var id=t.dataset.id;
      api({action:'delete',id:Number(id)}).then(function(){
        S.products=S.products.filter(function(p){return p.id!==id});delete S.cart[id];saveProducts();render();toast('Barang dihapus dari rak');
      }).catch(apiFail);
      return;
    }
    t.dataset.sure='1';t.textContent='Yakin hapus?';
    setTimeout(function(){if(t.isConnected){delete t.dataset.sure;t.textContent='Hapus'}},3000);
  },
  reset:function(t){
    if(t.dataset.sure){
      api({action:'reset'}).then(function(j){
        S.products=j.produk.map(norm);S.cart={};saveProducts();render();toast('Barang awal dikembalikan');LS.set('pm_seed_seafood',false);seedSeafood();
      }).catch(apiFail);
      return;
    }
    t.dataset.sure='1';t.textContent='Yakin? Semua perubahan hilang';
    setTimeout(function(){if(t.isConnected){delete t.dataset.sure;t.textContent='Kembalikan barang awal'}},3500);
  }
};
document.addEventListener('click',function(e){
  if(e.target.classList&&e.target.classList.contains('overlay')&&$('#editForm')){S.editId=null;closeModal();return}
  var t=e.target.closest('[data-act]');if(!t)return;
  var f=A[t.dataset.act];if(f)f(t,e);
});
document.addEventListener('submit',function(e){
  e.preventDefault();
  var f=e.target.dataset.form;
  if(f==='total')checkTotal();else if(f==='change')checkChange();else if(f==='add')addProduct(e.target);else if(f==='profil')saveProfile(e.target);else if(f==='edit')saveEdit(e.target);
});
document.addEventListener('input',function(e){
  var t=e.target;
  if(t.classList&&t.classList.contains('sinput')){
    if(t.id==='shopSearch'){S.q=t.value;var sh=$('#shelves');if(sh){sh.innerHTML=shelvesHTML();requestAnimationFrame(shelfArrows)}}
    else{S.aq=t.value;updateAdminList()}
    var cl=t.parentElement.querySelector('.sclear');if(cl)cl.hidden=!t.value;
    return;
  }
  if(t.classList&&t.classList.contains('mi')){
    var d=t.value.replace(/\D/g,'').slice(0,9);
    t.value=d?fmt(Number(d)):'';
    var m=$('#msg');if(m&&m.textContent)m.textContent='';
  }
  if(t.form&&t.form.id==='addForm')updatePreview();
  if(t.form&&t.form.id==='editForm')updateEditPreview();
});
document.addEventListener('change',function(e){
  var t=e.target;
  if(t.id==='pfFoto'){loadFoto(t.files&&t.files[0]);t.value='';return}
  if(t.form&&t.form.id==='addForm'){updatePreview();return}
  if(t.form&&t.form.id==='editForm'){updateEditPreview();return}
  var f=t.dataset&&t.dataset.f;if(!f)return;
  var row=t.closest('tr');if(!row)return;
  var p=byId(row.dataset.id);if(!p)return;
  if(f==='name'){
    var v=t.value.trim().slice(0,24);
    if(!v){t.value=p.name;toast('Nama barang tidak boleh kosong.');return}
    p.name=v;
  }else if(f==='price'){
    var n=parseMoney(t.value);
    if(n===null||n<100){t.value=fmt(p.price);toast('Harga minimal Rp 100.');return}
    p.price=n;t.value=fmt(n);
  }else if(f==='cat'){
    p.cat=t.value;
  }else if(f==='emoji'){
    var ch=Array.from(t.value.trim())[0];
    if(!ch){t.value=p.emoji;return}
    p.emoji=ch;t.value=ch;
  }
  saveProducts();
  api({action:'update',id:Number(p.id),name:p.name,price:p.price,cat:p.cat,emoji:p.emoji}).then(function(){toast('Tersimpan di database')}).catch(function(e){apiFail(e);reloadProducts()});
});
document.addEventListener('keydown',function(e){
  if(e.key==='Escape'&&$('#editForm')){S.editId=null;closeModal();return}
  var lk=e.target.closest&&e.target.closest('.d-level.link');
  if(lk&&(e.key==='Enter'||e.key===' ')){e.preventDefault();go('profil');return}
  if(e.code==='Space'&&S.view==='kasir'&&S.co&&S.co.stage==='scan'&&e.target===document.body){e.preventDefault();scanNext()}
});

seedSeafood();
setInterval(autoTick,1000);
setInterval(chTick,250);
render();
})();
</script>

</body>
</html>