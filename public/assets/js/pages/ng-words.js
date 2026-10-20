/**
 * A-06 NGワード管理：一致方式の説明の切り替えと、「ためしに判定する」
 * 判定はブラウザ側の簡易なもの（表示の補助）。本番の判定はサーバー側で行う。
 */
const form = document.querySelector('[data-js-ng-match]')?.closest('form');
if (form) {
  const help = form.querySelector('[data-js-ng-match-help]');
  const tryInput = form.querySelector('[data-js-ng-try]');
  const tryResult = form.querySelector('[data-js-ng-try-result]');
  const wordInput = form.elements.word;
  const defaultResult = tryResult ? tryResult.textContent : '';

  function matchType() {
    const checked = form.querySelector('[name="match_type"]:checked');
    return checked ? checked.value : 'partial';
  }

  function updateHelp() {
    const checked = form.querySelector('[name="match_type"]:checked');
    if (help && checked) help.textContent = checked.dataset.jsHelp || '';
  }

  function judge() {
    if (!tryInput || !tryResult) return;
    const text = tryInput.value;
    const word = wordInput ? wordInput.value : '';
    if (text === '' || word === '') {
      tryResult.textContent = defaultResult;
      return;
    }
    let hit = false;
    try {
      if (matchType() === 'exact') hit = text === word;
      else if (matchType() === 'partial') hit = text.includes(word);
      else hit = new RegExp(word, 'u').test(text);
    } catch (_) {
      tryResult.textContent = '正しい正規表現ではありません';
      return;
    }
    tryResult.textContent = hit ? '一致します（この文章は判定の対象になります）' : '一致しません';
  }

  form.querySelectorAll('[name="match_type"]').forEach((radio) => {
    radio.addEventListener('change', () => { updateHelp(); judge(); });
  });
  if (tryInput) tryInput.addEventListener('input', judge);
  if (wordInput) wordInput.addEventListener('input', judge);
  updateHelp();
}
