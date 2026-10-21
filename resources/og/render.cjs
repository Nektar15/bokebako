/**
 * OGP 画像の Vercel 版（GD のない Vercel 仮置きでだけ使う。ADR 0014）
 *
 * 本番（Xserver）の画像は PHP の app/Libraries/OgpImage.php が描く。ここは同じ配置・色を @vercel/og（Satori）で描く。
 * 配置の数字は OgpImage.php と同じ（1200×630）。デザインを変えたら両方を直す。
 */
const fs = require('fs');
const path = require('path');

const W = 1200;
const H = 630;
const C = { paper: '#F4EEE2', ink: '#1D1A16', sub: '#4A433B', shu: '#D6401E', kin: '#F2C230', card: '#FFFDF8' };
const DIR = __dirname;
const asset = (name) => 'data:image/png;base64,' + fs.readFileSync(path.join(DIR, name)).toString('base64');
let cache = null;
const assets = () => (cache ??= { backdrop: asset('backdrop.png'), logo: asset('logo.png'), font: fs.readFileSync(path.join(DIR, 'NotoSansJP-Bold.woff')) });

const el = (type, style, children = []) => ({ type, props: { style, children } });
const abs = (x, y, w, h, style = {}, children = []) => el('div', { position: 'absolute', left: x, top: y, width: w, height: h, display: 'flex', ...style }, children);

/** 文字数から、箱に収まるいちばん大きい文字の大きさ（OgpImage の段落と同じく、最大から2ずつ下げる） */
function fitSize(text, width, height, max, min) {
  const len = [...text].reduce((n, ch) => n + (ch.charCodeAt(0) < 0x2000 ? 0.55 : 1), 0);
  for (let s = max; s >= min; s -= 2) {
    const perLine = Math.max(1, Math.floor((width - s) / s));
    const lines = Math.ceil(len / perLine);
    if (lines * s * 1.45 <= height) return s;
  }
  return min;
}

function card(x1, y1, x2, y2, fill, shadow, children, extra = {}) {
  return abs(x1, y1, x2 - x1, y2 - y1, {
    backgroundColor: fill, border: `4px solid ${C.ink}`, borderRadius: 24, boxShadow: shadow ? `${shadow}px ${shadow}px 0 ${C.ink}` : 'none',
    alignItems: 'center', justifyContent: 'center', ...extra,
  }, children);
}

function centered(text, width, height, max, min, color = C.ink) {
  const size = fitSize(text, width, height, max, min);
  return el('div', { display: 'flex', width, maxHeight: height, overflow: 'hidden', justifyContent: 'center', textAlign: 'center', fontSize: size, lineHeight: 1.45, color }, text);
}

function oneLine(text, width, size, color) {
  return el('div', { display: 'block', maxWidth: width, fontSize: size, color, whiteSpace: 'nowrap', overflow: 'hidden', textOverflow: 'ellipsis' }, text);
}

function circle(cx, cy, r, fill, label, size) {
  return abs(cx - r, cy - r, r * 2, r * 2, { borderRadius: r, backgroundColor: fill, color: C.card, fontSize: size, alignItems: 'center', justifyContent: 'center' }, label);
}

function brand(x, y) {
  return abs(x, y, 300, 52, { alignItems: 'center', gap: 12 }, [
    { type: 'img', props: { src: assets().logo, width: 52, height: 52 } },
    el('div', { fontSize: 30, color: C.ink }, 'ぼけばこ'),
  ]);
}

function backdrop() {
  return { type: 'img', props: { src: assets().backdrop, width: W, height: H, style: { position: 'absolute', left: 0, top: 0 } } };
}

function recruit(d) {
  return [
    backdrop(),
    brand(80, 56),
    abs(W - 80 - 360, 56, 360, 48, { justifyContent: 'flex-end' }, [
      el('div', { display: 'flex', height: 48, padding: '0 20px', alignItems: 'center', backgroundColor: C.kin, border: `2px solid ${C.ink}`, fontSize: 20, color: C.ink }, d.texts.recruit_pill),
    ]),
    card(130, 150, W - 130, 500, C.card, 8, [centered(d.odai, W - 340, 300, 64, 34)]),
    abs(0, H - 18, W, 18, { backgroundColor: C.shu }),
  ];
}

function answer(d) {
  return [
    backdrop(),
    card(110, 40, W - 110, 120, C.card, 6, [oneLine(d.texts.answer_prefix + d.odai, W - 300, 30, C.ink)]),
    card(110, 150, W - 110, 520, C.kin, 8, [centered(d.answer, W - 340, 326, 64, 32)]),
    abs(0, 548, W, H - 548, { backgroundColor: C.paper }),
    brand(70, 560),
  ];
}

function result(d) {
  const nodes = [
    abs(W - 92 - 50, 66 - 50, 100, 100, { borderRadius: 50, border: `4px solid ${C.shu}`, color: C.shu, fontSize: 18, alignItems: 'center', justifyContent: 'center', textAlign: 'center' }, d.texts.result_stamp),
    abs(160, 40, 880, 44, { justifyContent: 'center' }, [oneLine(d.texts.result_prefix + d.odai, 880, 28, C.ink)]),
  ];
  const ranked = [...d.ranked];
  if (ranked.length === 0) {
    nodes.push(abs(150, 240, 900, 120, { justifyContent: 'center', alignItems: 'center' }, [centered('（選出なし）', 900, 120, 48, 40, C.sub)]));
  } else {
    const first = ranked.shift();
    const bottom = ranked.length === 0 ? 530 : 364;
    nodes.push(card(140, 128, W - 140, bottom, C.kin, 8, [centered(first.body, W - 400, bottom - 150 - 22, ranked.length === 0 ? 76 : 60, 30)]));
    nodes.push(circle(170, 132, 34, C.shu, `${first.rank}位`, 24));
    const slots = ranked.length === 1 ? [[300, 900]] : [[70, 586], [614, 1130]];
    ranked.forEach((row, i) => {
      const [x1, x2] = slots[i];
      nodes.push(card(x1, 392, x2, 532, C.card, 6, [centered(row.body, x2 - x1 - 110, 116, 32, 20)]));
      nodes.push(circle(x1 + 24, 394, 25, C.ink, `${row.rank}位`, 17));
    });
  }
  nodes.push(brand(70, 552));
  if (d.count !== '') nodes.push(abs(W - 70 - 400, 566, 400, 36, { justifyContent: 'flex-end', fontSize: 24, color: C.ink }, d.count));
  return nodes;
}

/** @returns {{element: object, options: object}} */
function build(d) {
  const children = d.kind === 'recruit' ? recruit(d) : d.kind === 'answer' ? answer(d) : result(d);
  return {
    element: el('div', { position: 'relative', display: 'flex', width: W, height: H, backgroundColor: C.paper, fontFamily: 'NotoSansJP' }, children),
    options: { width: W, height: H, fonts: [{ name: 'NotoSansJP', data: assets().font, weight: 700, style: 'normal' }] },
  };
}

module.exports = { build, W, H };
