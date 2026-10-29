(() => {
'use strict';
const form=document.getElementById('process-form');
if(!form) return;
const fileInput=document.getElementById('image-file'), urlInput=document.getElementById('image-url');
const dropzone=document.getElementById('dropzone'), fileInfo=document.getElementById('file-info');
const statusBox=document.getElementById('status'), processBtn=document.getElementById('process-btn');
const empty=document.getElementById('empty-state'), editor=document.getElementById('editor');
const originalPreview=document.getElementById('original-preview'), resultPreview=document.getElementById('result-preview');
const resultFrame=document.getElementById('result-frame'), resultBadge=document.getElementById('result-badge');
const bgColour=document.getElementById('bg-colour');
let originalObjectUrl=null, resultUrl=null, downloadUrl=null, transparentImage=null;
const say=(message,type='info')=>{statusBox.textContent=message;statusBox.className='status mt-3 '+type;};
function showFile(file){
 if(!file)return;
 if(!['image/jpeg','image/png'].includes(file.type)){fileInput.value='';say('Please choose a JPG or PNG image.','error');return;}
 if(file.size>window.REMOVEBG_CONFIG.maxUploadBytes){fileInput.value='';say('That file is too large for the configured limit.','error');return;}
 fileInfo.textContent=`${file.name} · ${(file.size/1024/1024).toFixed(2)} MB`;
 if(originalObjectUrl)URL.revokeObjectURL(originalObjectUrl);
 originalObjectUrl=URL.createObjectURL(file);originalPreview.src=originalObjectUrl;
 urlInput.value='';
}
fileInput.addEventListener('change',()=>showFile(fileInput.files[0]));
urlInput.addEventListener('input',()=>{if(urlInput.value.trim())fileInput.value='';});
['dragenter','dragover'].forEach(ev=>dropzone.addEventListener(ev,e=>{e.preventDefault();dropzone.classList.add('dragging');}));
['dragleave','drop'].forEach(ev=>dropzone.addEventListener(ev,e=>{e.preventDefault();dropzone.classList.remove('dragging');}));
dropzone.addEventListener('drop',e=>{const f=e.dataTransfer.files[0];if(!f)return;try{fileInput.files=e.dataTransfer.files;}catch(_e){}showFile(f);});
form.addEventListener('submit',async e=>{
 e.preventDefault();const file=fileInput.files[0], imageUrl=urlInput.value.trim();
 if(!file&&!imageUrl){say('Choose an image or enter a public HTTPS image URL.','error');return;}
 if(file&&imageUrl){say('Choose either a file or a URL, not both.','error');return;}
 if(imageUrl){try{const u=new URL(imageUrl);if(u.protocol!=='https:')throw Error();}catch(_e){say('Please enter a valid HTTPS image URL.','error');return;}}
 processBtn.disabled=true;processBtn.innerHTML='<span class="spinner-border spinner-border-sm me-2"></span>Processing with Pixelcut…';
 resultBadge.textContent='Processing';say('Sending your image to Pixelcut AI. This may take a little while…','info');
 try{
  const fd=new FormData(form);const response=await fetch('process.php',{method:'POST',body:fd,credentials:'same-origin',headers:{'Accept':'application/json'}});
  const data=await response.json().catch(()=>null);
  if(!response.ok||!data||!data.ok)throw new Error(data?.error||`Request failed (HTTP ${response.status}). Check PHP logs.`);
  resultUrl=data.result_url;downloadUrl=data.download_url;
  if(data.original_url.startsWith('data:'))originalPreview.src=data.original_url;
  else if(!file)originalPreview.src=data.original_url;
  resultPreview.src=resultUrl;
  await new Promise((resolve,reject)=>{resultPreview.onload=resolve;resultPreview.onerror=()=>reject(new Error('Could not load the processed image.'));});
  transparentImage=new Image();transparentImage.crossOrigin='same-origin';transparentImage.src=resultUrl;
  await new Promise((resolve,reject)=>{transparentImage.onload=resolve;transparentImage.onerror=()=>reject(new Error('Could not prepare image export.'));});
  empty.hidden=true;editor.hidden=false;resultBadge.textContent='Ready';say('Background removed successfully.','success');setBackground('transparent');
 }catch(err){say(err.message||'Processing failed. Please try again.','error');resultBadge.textContent='Failed';}
 finally{processBtn.disabled=false;processBtn.innerHTML='<i class="fa-solid fa-wand-magic-sparkles me-2"></i>Remove background';}
});
function setBackground(colour){resultFrame.style.background=colour==='transparent'?'transparent':colour;resultFrame.classList.toggle('checkerboard',colour==='transparent');}
document.querySelectorAll('.bg-choice').forEach(btn=>btn.addEventListener('click',()=>{setBackground(btn.dataset.colour);if(btn.dataset.colour.startsWith('#'))bgColour.value=btn.dataset.colour;}));
bgColour.addEventListener('input',()=>setBackground(bgColour.value));
function exportImage(colour){
 if(!transparentImage||!transparentImage.naturalWidth){say('Wait for the result image to load first.','error');return;}
 try{const canvas=document.createElement('canvas');canvas.width=transparentImage.naturalWidth;canvas.height=transparentImage.naturalHeight;
 const ctx=canvas.getContext('2d');if(colour){ctx.fillStyle=colour;ctx.fillRect(0,0,canvas.width,canvas.height);}ctx.drawImage(transparentImage,0,0);
 canvas.toBlob(blob=>{if(!blob){say('Your browser could not export this image.','error');return;}const a=document.createElement('a');const u=URL.createObjectURL(blob);a.href=u;a.download=colour?'removebg-with-background.png':'removebg-transparent.png';a.click();setTimeout(()=>URL.revokeObjectURL(u),1000);},'image/png');
 }catch(_e){say('Image export failed. Try a smaller image or another browser.','error');}
}
document.getElementById('download-png').addEventListener('click',()=>exportImage(null));
document.getElementById('download-colour').addEventListener('click',()=>exportImage(bgColour.value));
document.getElementById('reset-btn').addEventListener('click',()=>{form.reset();fileInfo.textContent='';statusBox.textContent='';editor.hidden=true;empty.hidden=false;resultBadge.textContent='Waiting for image';originalPreview.removeAttribute('src');resultPreview.removeAttribute('src');transparentImage=null;resultUrl=null;downloadUrl=null;if(originalObjectUrl)URL.revokeObjectURL(originalObjectUrl);originalObjectUrl=null;setBackground('transparent');});
})();
