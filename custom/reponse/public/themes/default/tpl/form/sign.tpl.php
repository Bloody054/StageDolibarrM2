<?php
$value = $line->value;
?>
<style>
  .sig-wrap{max-width:480px}
  .sig-box{
    border:1px solid #ccc; border-radius:8px;
    touch-action:none; /* important: pas de scroll/pinch sur mobile */
  }
  .sig-tools{margin:.5rem 0; display:flex; gap:.5rem}
</style>

<div class="section">
	<div class="container">
		<div class="row justify-content-center align-items-center">
			<div class="col-10 col-md-10 col-lg-8 text-center">
                <?php $reponse->include_once('tpl/layouts/progress.tpl.php', array('progress' => $progress, 'show_progressbar'=>$reponse->questionnaire->progressbar)); ?>

                <h2 class="h1 mb-5 font-weight-light"><?php echo $line->label; ?></h2>
				<p class="lead"><?php echo $line->help; ?></p>
                <form id="report" name="report" action="<?php echo $site->makeUrl('report.php'); ?>" method="post">
                    <input type="hidden" name="token" value="<?php echo $_SESSION['newtoken']; ?>" />
                    <input type="hidden" name="id" value="<?php echo $reponse->id; ?>">
                    <input type="hidden" name="current" value="<?php echo $current; ?>">
                    <input type="hidden" name="signature_png" id="signatureField">
                    <div class="form-group mb-5">
                        <!-- <input class="form-control" name="<?php echo $line->code; ?>" id="question" placeholder="" type="text" aria-label="<?php echo $line->code; ?>" value="<?php echo $value; ?>" <?php echo $line->mandatory ? 'required' : '' ?>> -->
                        <canvas id="sigCanvas" width="480" height="200" class="sig-box"></canvas>
                        <div class="sig-tools">
                            <button type="button" id="clearBtn">Effacer tout</button>
                            <button type="button" id="undoBtn">Effacer dernier trait</button>
                            <!-- <button type="button" id="savePngBtn">Télécharger PNG</button> -->
                      </div>
                    </div>

                    <button type="submit" name="next" class="btn btn-block btn-success"><?php echo $langs->trans('ReponseNextQuestion'); ?></button>
                    <?php if ($displayPreviousButton): ?>
                        <button type="submit" name="previous" class="btn btn-block btn-outline-success mb-1"><?php echo $langs->trans('ReponsePreviousQuestion'); ?></button>
                    <?php endif; ?>
                </form>
			</div>
		</div>
	</div>
</div>

<script>
(() => {
  const canvas = document.getElementById('sigCanvas');
  const ctx = canvas.getContext('2d');
  const hidden = document.getElementById('signatureField');
  const clearBtn = document.getElementById('clearBtn');
  const undoBtn = document.getElementById('undoBtn');
  const savePngBtn = document.getElementById('savePngBtn');

  // DPI / retina
  function fitToDevicePixelRatio() {
    const ratio = Math.max(window.devicePixelRatio || 1, 1);
    const cssW = canvas.width, cssH = canvas.height; // attributes are our logical size
    canvas.style.width = cssW + 'px';
    canvas.style.height = cssH + 'px';
    canvas.width = cssW * ratio;
    canvas.height = cssH * ratio;
    ctx.scale(ratio, ratio);
    ctx.lineWidth = 2;
    ctx.lineCap = 'round';
    ctx.lineJoin = 'round';
  }
  fitToDevicePixelRatio();

  let drawing = false;
  let lastX = 0, lastY = 0;
  const strokes = []; // pour undo
  let currentStroke = [];

  function posFromEvent(e) {
    const rect = canvas.getBoundingClientRect();
    const p = e.touches ? e.touches[0] : e;
    return { x: p.clientX - rect.left, y: p.clientY - rect.top };
  }

  function start(e) {
    e.preventDefault();
    drawing = true;
    currentStroke = [];
    const {x,y} = posFromEvent(e);
    lastX = x; lastY = y;
    currentStroke.push([x,y]);
  }
  function move(e) {
    if (!drawing) return;
    e.preventDefault();
    const {x,y} = posFromEvent(e);
    ctx.beginPath();
    ctx.moveTo(lastX, lastY);
    ctx.lineTo(x, y);
    ctx.stroke();
    lastX = x; lastY = y;
    currentStroke.push([x,y]);
  }
  function end(e) {
    if (!drawing) return;
    drawing = false;
    if (currentStroke.length > 1) strokes.push(currentStroke);
    syncHiddenField(); // mettre à jour la valeur envoyée
  }

  // Pointer events (fonctionne souris + tactile)
  canvas.addEventListener('pointerdown', start);
  canvas.addEventListener('pointermove', move);
  canvas.addEventListener('pointerup', end);
  canvas.addEventListener('pointerleave', end);
  canvas.addEventListener('pointercancel', end);

  // Touch fallback (iOS vieux)
  canvas.addEventListener('touchstart', start, {passive:false});
  canvas.addEventListener('touchmove', move, {passive:false});
  canvas.addEventListener('touchend', end);

  function redrawAll() {
    // reset
    const style = getComputedStyle(canvas);
    const w = parseInt(style.width), h = parseInt(style.height);
    ctx.clearRect(0,0,w,h);
    ctx.lineWidth = 2;
    ctx.lineCap = 'round';
    ctx.lineJoin = 'round';
    for (const s of strokes) {
      for (let i=1;i<s.length;i++){
        ctx.beginPath();
        ctx.moveTo(s[i-1][0], s[i-1][1]);
        ctx.lineTo(s[i][0],   s[i][1]);
        ctx.stroke();
      }
    }
  }

  function clearAll() {
    strokes.length = 0;
    redrawAll();
    syncHiddenField();
  }

  function undo() {
    strokes.pop();
    redrawAll();
    syncHiddenField();
  }

  function syncHiddenField() {
    // convertit en PNG base64 (fond blanc pour éviter la transparence)
    const tmp = document.createElement('canvas');
    tmp.width = canvas.width; tmp.height = canvas.height;
    const tctx = tmp.getContext('2d');
    // fond blanc
    tctx.fillStyle = '#fff';
    tctx.fillRect(0,0,tmp.width,tmp.height);
    // dessine le canvas affiché à l’échelle 1 (copie bitmap)
    tctx.drawImage(canvas, 0, 0);
    hidden.value = tmp.toDataURL('image/png'); // "data:image/png;base64,...."
  }

  clearBtn.addEventListener('click', clearAll);
  undoBtn.addEventListener('click', undo);
  savePngBtn.addEventListener('click', () => {
    // téléchargement direct du PNG
    const url = (function(){
      const tmp = document.createElement('canvas');
      tmp.width = canvas.width; tmp.height = canvas.height;
      const tctx = tmp.getContext('2d');
      tctx.fillStyle = '#fff'; tctx.fillRect(0,0,tmp.width,tmp.height);
      tctx.drawImage(canvas,0,0);
      return tmp.toDataURL('image/png');
    })();
    const a = document.createElement('a');
    a.href = url; a.download = 'signature.png';
    a.click();
  });

  // init valeur vide
  syncHiddenField();
})();
</script>