(function(){
  document.addEventListener('DOMContentLoaded', function(){
    const btn       = document.getElementById('generate');
    const container = document.getElementById('qr-container');
    const input     = document.getElementById('qr-text');
    const select    = document.getElementById('shape-select');

    if (!btn || !container || !input || !select) {
      return;
    }

    async function generateQR() {
      container.innerHTML = '<p>Cargando…</p>';
      const params = new URLSearchParams({
        action: 'qrshapes_generate',
        data:   input.value
      });
      let text;
      try {
        const res = await fetch(qrshapes.ajax_url, {
          method:  'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body:    params.toString()
        });
        if (!res.ok) {
          const err = await res.text();
          container.innerHTML = `<p style="color:red;">${err}</p>`;
          return;
        }
        text = (await res.text()).trim().replace(/^\uFEFF/, '');
      }
      catch (e) {
        container.innerHTML = `<p style="color:red;">Error de red: ${e.message}</p>`;
        return;
      }

      const parser = new DOMParser();
      const doc    = parser.parseFromString(text, 'image/svg+xml');

      if (doc.querySelector('parsererror')) {
        console.error('SVG parse error:', text);
        container.innerHTML = '<p style="color:red;">Error al procesar SVG.</p>';
        return;
      }

      const svg = doc.documentElement;
      svg.id = 'qr-svg';
      container.innerHTML = '';
      container.appendChild(svg);
      applyShape();
    }

    function applyShape() {
      const svg   = document.getElementById('qr-svg');
      const shape = select.value;
      const xmlns = 'http://www.w3.org/2000/svg';

      svg.querySelectorAll('rect').forEach(rect => {
        const x    = parseFloat(rect.getAttribute('x'));
        const y    = parseFloat(rect.getAttribute('y'));
        const w    = parseFloat(rect.getAttribute('width'));
        const fill = rect.getAttribute('fill') || '#000';

        let el;
        switch (shape) {
          case 'circle':
            el = document.createElementNS(xmlns,'circle');
            el.setAttribute('cx', x + w/2);
            el.setAttribute('cy', y + w/2);
            el.setAttribute('r',  w/2);
            el.setAttribute('fill', fill);
            break;
          case 'dot':
            el = document.createElementNS(xmlns,'circle');
            el.setAttribute('cx', x + w/2);
            el.setAttribute('cy', y + w/2);
            el.setAttribute('r',  w * 0.25);
            el.setAttribute('fill', fill);
            break;
          case 'diamond':
            const cx = x + w/2, cy = y + w/2, r = w/2;
            el = document.createElementNS(xmlns,'polygon');
            el.setAttribute('points', `${cx},${cy-r} ${cx+r},${cy} ${cx},${cy+r} ${cx-r},${cy}`);
            el.setAttribute('fill', fill);
            break;
          case 'square':
          default:
            return;
        }
        rect.parentNode.replaceChild(el, rect);
      });
    }

    btn.addEventListener('click', function(e){
      e.preventDefault();
      generateQR();
    });
    select.addEventListener('change', applyShape);
    generateQR();
  });
})();
