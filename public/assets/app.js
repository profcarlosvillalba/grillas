'use strict';
(() => {
  let toastTimer;
  const toast = (message, error=false) => {
    const node=document.getElementById('toast');
    if(!node) return;
    node.textContent=message; node.classList.toggle('error',error); node.hidden=false;
    clearTimeout(toastTimer); toastTimer=setTimeout(()=>node.hidden=true,error?9000:3500);
  };
  const requestJson = async data => {
    const response=await fetch(data.action,{method:'POST',body:data.body,headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest'},credentials:'same-origin'});
    if(response.status===419) throw new Error('La sesión venció. Actualizá la página antes de continuar.');
    let result;
    try { result=await response.json(); } catch { throw new Error('No se pudo guardar. Revisá la conexión y tu sesión antes de reintentar.'); }
    if(!response.ok) throw new Error(result.errors ? Object.values(result.errors).flat().join(' ') : (result.message || 'No se pudo guardar el registro.'));
    return result;
  };
  const savedRow = html => {
    const template=document.createElement('template'); template.innerHTML=html.trim();
    return template.content.firstElementChild;
  };
  const counters = result => {
    ['total','pendientes','asignados'].forEach(key=>document.querySelectorAll('[data-count="'+key+'"]').forEach(node=>node.textContent=result[key]));
  };
  const scrollToInput = input => {
    if(document.activeElement===input) input.scrollIntoView({block:'nearest'});
  };

  // Cada envío conserva su propio código, tipo y fila hasta recibir la confirmación.
  const scanStates=new WeakMap();
  const unsaved=new Set();
  const scanState = form => {
    if(!scanStates.has(form)) scanStates.set(form,{queue:[],running:false,jobs:new Set()});
    return scanStates.get(form);
  };
  const scanStatus = form => {
    const jobs=[...scanState(form).jobs];
    const pending=jobs.filter(job=>job.status!=='failed').length;
    const failed=jobs.length-pending;
    const node=form.querySelector('[data-scan-status]');
    if(node) node.textContent=(pending ? pending+' equipo(s) guardándose. ' : '')+(failed ? failed+' sin guardar: revisá las filas marcadas.' : (pending ? '' : 'Listo para el siguiente código.'));
  };
  const removePending = (form,job) => {
    scanState(form).jobs.delete(job); unsaved.delete(job); job.row.remove(); scanStatus(form);
    const rows=document.querySelector('[data-rows="'+form.dataset.planilla+'"]');
    if(!rows.children.length) {
      const row=document.createElement('tr'); row.dataset.empty='';
      const cell=document.createElement('td'); cell.colSpan=6; cell.textContent='Todavía no hay equipos registrados.';
      cell.className='empty-row'; row.append(cell); rows.append(row);
    }
  };
  const runScans = async form => {
    const state=scanState(form);
    if(state.running) return;
    state.running=true;
    while(state.queue.length) {
      const job=state.queue.shift(); job.status='saving'; job.label.textContent='Guardando…';
      job.row.setAttribute('aria-busy','true');
      try {
        const result=await requestJson({action:form.action,body:job.body});
        job.row.replaceWith(savedRow(result.html));
        state.jobs.delete(job); unsaved.delete(job);
        const counter=document.querySelector('[data-sheet-total="'+form.dataset.planilla+'"]');
        if(counter) counter.textContent=result.planilla_total;
        form.closest('.sheet').querySelector('.sheet-meta form')?.remove();
        counters(result); toast(result.message);
      } catch(error) {
        job.status='failed'; job.row.classList.add('scan-failed');
        job.label.textContent='Sin guardar'; job.error.textContent=error.message; job.error.hidden=false;
        job.actions.hidden=false; toast(error.message,true);
      } finally {
        job.row.removeAttribute('aria-busy'); scanStatus(form);
        scrollToInput(form.querySelector('[data-mac]'));
      }
    }
    state.running=false;
  };
  const enqueueScan = form => {
    if(!form.reportValidity()) return;
    const input=form.querySelector('[data-mac]');
    if(!input.value.trim()) return;
    const select=form.querySelector('[name="tipo_equipo_id"]');
    const job={body:new FormData(form),status:'queued',row:document.createElement('tr')};
    job.row.dataset.pendingScan=''; job.row.className='scan-pending';
    const cell = text => { const node=document.createElement('td'); node.textContent=text; job.row.append(node); return node; };
    cell(select.selectedOptions[0].textContent);
    cell(input.value).className='mac-cell';
    cell(''); cell('');
    job.label=document.createElement('span'); job.label.className='badge pending'; job.label.textContent='En cola';
    const statusCell=cell(''); statusCell.append(job.label);
    job.error=document.createElement('small'); job.error.className='scan-error'; job.error.setAttribute('role','alert'); job.error.hidden=true; statusCell.append(job.error);
    const actionsCell=cell(''); job.actions=document.createElement('div'); job.actions.className='pending-actions'; job.actions.hidden=true; actionsCell.append(job.actions);
    const action = (text,handler) => {
      const button=document.createElement('button'); button.type='button'; button.className='btn tiny secondary'; button.textContent=text;
      button.addEventListener('click',handler); job.actions.append(button);
    };
    action('Reintentar',()=>{
      if(job.status!=='failed') return;
      job.status='queued'; job.error.hidden=true; job.actions.hidden=true; job.row.classList.remove('scan-failed');
      job.label.textContent='En cola'; scanState(form).queue.push(job); scanStatus(form); runScans(form);
    });
    action('Corregir',()=>{
      if(input.value.trim()) { toast('Cargá primero el código que estás escribiendo; después podés corregir esta fila.',true); input.focus(); return; }
      input.value=job.body.get('mac'); select.value=job.body.get('tipo_equipo_id');
      removePending(form,job); input.focus(); input.select(); scrollToInput(input);
    });
    action('Descartar',()=>removePending(form,job));
    const rows=document.querySelector('[data-rows="'+form.dataset.planilla+'"]');
    rows.querySelector('[data-empty]')?.remove(); rows.append(job.row);
    const state=scanState(form); state.jobs.add(job); state.queue.push(job); unsaved.add(job);
    input.value=''; input.focus({preventScroll:true}); scrollToInput(input); scanStatus(form); runScans(form);
  };
  window.addEventListener('beforeunload',event=>{
    if(unsaved.size) { event.preventDefault(); event.returnValue=''; }
  });
  document.addEventListener('keydown',event=>{
    const input=event.target;
    if(!(input instanceof HTMLInputElement) || !input.matches('[data-mac]')) return;
    if(!['Tab','Enter'].includes(event.key) || event.shiftKey || event.ctrlKey || event.altKey || event.metaKey || event.isComposing || !input.value.trim()) return;
    const form=input.form;
    if(form?.dataset.ajax!=='scan') return;
    event.preventDefault();
    if(!event.repeat) form.requestSubmit();
  });
  document.querySelectorAll('[data-team-form]').forEach(form=>{
    const work=form.querySelector('[data-work]'), dupla=form.querySelector('[data-dupla]');
    const swap=form.querySelector('[data-swap-team]');
    const preview=()=>{
      const selected=work.selectedOptions[0];
      const original=work.value && work.value===form.dataset.originalWork;
      const code=original ? form.dataset.originalCode : selected?.dataset.legajo;
      const name=original ? form.dataset.originalName : selected?.dataset.name;
      form.querySelector('[data-work-code]').textContent=code || 'Se completa al elegir el productivo';
      const pair=dupla.selectedOptions[0]?.dataset.name;
      form.querySelector('[data-team-preview]').textContent=work.value ? ((code || 'SIN LEGAJO')+' '+name+(pair ? ' - '+pair : '')).toLocaleUpperCase('es-AR') : 'Seleccioná el productivo y la dupla';
      swap.disabled=!work.value || !dupla.value;
    };
    work.addEventListener('change',preview); dupla.addEventListener('change',preview);
    swap.addEventListener('click',()=>{
      const option=[...work.options].find(o=>o.value===dupla.value);
      if(!option || option.disabled) { toast('Completá el legajo de la dupla antes de elegirla como productivo.',true); return; }
      const previous=work.value; work.value=dupla.value; dupla.value=previous; preview();
    });
    preview();
  });
  document.addEventListener('click',event=>{
    if(event.target.closest('[data-print]')) window.print();
    const menu=event.target.closest('[data-menu]');
    if(menu) {
      const open=document.getElementById('sidebar').classList.toggle('open');
      menu.setAttribute('aria-expanded',String(open));
    }
  });
  document.addEventListener('submit',async event=>{
    const form=event.target;
    if(!(form instanceof HTMLFormElement)) return;
    if(form.dataset.confirm && !window.confirm(form.dataset.confirm)) { event.preventDefault(); return; }
    if(!form.dataset.ajax || !window.fetch) return;
    event.preventDefault();
    if(form.dataset.ajax==='scan') { enqueueScan(form); return; }
    if(form.dataset.busy==='true') return;
    form.dataset.busy='true';
    const button=form.querySelector('button[type="submit"], button:not([type])');
    const label=button?.textContent;
    if(button) { button.disabled=true; button.textContent='Guardando…'; }
    try {
      const result=await requestJson({action:form.action,body:new FormData(form)});
      if(document.getElementById('daily-grid')?.dataset.filtered==='true') { window.location.reload(); return; }
      document.getElementById('registro-'+result.id)?.replaceWith(savedRow(result.html));
      counters(result); toast(result.message);
    } catch(error) { toast(error.message,true); }
    finally {
      form.dataset.busy='false';
      if(button) { button.disabled=false; button.textContent=label; }
    }
  });
  const hash=location.hash && document.getElementById(location.hash.slice(1));
  const firstForm=(hash || document).querySelector('[data-ajax="scan"]');
  if(firstForm) {
    const type=firstForm.querySelector('[name="tipo_equipo_id"]');
    (type.value ? firstForm.querySelector('[data-mac]') : type).focus({preventScroll:!hash});
  }
})();
