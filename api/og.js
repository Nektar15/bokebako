/**
 * Vercel 仮置きの OGP 画像（GET /og-image/{kind}/{key}.png → ここ。ADR 0014）
 *
 * Vercel の PHP には GD がなく画像を作れないので、中身を PHP（/api/v1/ogp/{kind}/{key}）から受け取り、
 * resources/og/render.cjs（app/Libraries/OgpImage.php と同じ配置）で描く。本番（Xserver）では使わない。
 */
const { ImageResponse } = require('@vercel/og');
const { build } = require('../resources/og/render.cjs');

const KINDS = ['recruit', 'answer', 'result'];

module.exports = async (req, res) => {
  const url = new URL(req.url, `https://${req.headers.host}`);
  const kind = url.searchParams.get('kind') || '';
  const key = url.searchParams.get('key') || '';
  // 中身を取りにいく先（手元で確かめるときは OG_ORIGIN=http://127.0.0.1:8090 のように渡す）
  const origin = process.env.OG_ORIGIN || `https://${req.headers['x-forwarded-host'] || req.headers.host}`;
  const fallback = () => {
    res.statusCode = 302;
    res.setHeader('Location', `${origin}/assets/img/ogp-default.png`);
    res.setHeader('Cache-Control', 'public, max-age=60');
    res.end();
  };

  if (!KINDS.includes(kind) || !/^[A-Za-z0-9]{1,32}$/.test(key)) return fallback();

  try {
    const api = await fetch(`${origin}/api/v1/ogp/${kind}/${key}`, { headers: { Accept: 'application/json' } });
    if (!api.ok) return fallback();
    const json = await api.json();
    const { element, options } = build(json.data);
    const image = new ImageResponse(element, options);
    const body = Buffer.from(await image.arrayBuffer());

    res.statusCode = 200;
    res.setHeader('Content-Type', 'image/png');
    // 確定・非表示などで中身が変わるので、CDN には5分だけ置く（プレビューは置かない）
    res.setHeader('Cache-Control', key === 'preview' ? 'no-store' : 'public, max-age=300, s-maxage=300');
    res.end(body);
  } catch (e) {
    console.error('OGP の生成に失敗', e);
    fallback();
  }
};
