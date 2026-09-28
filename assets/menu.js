/* SiteForge — экран «Настройка меню» (drag&drop как в WordPress) */
(function(){
 var tree=document.getElementById('menu-tree');
 if(tree){
  var items=[].slice.call(tree.querySelectorAll('.menu-item'));
  var byId={}; items.forEach(function(li){byId[li.getAttribute('data-id')]=li;});

  function dep(li){return parseInt(li.getAttribute('data-depth')||'0',10);}
  function setDepth(li,d){li.setAttribute('data-depth',d);li.style.marginLeft=(d*28)+'px';}
  function parentOf(li){var d=dep(li);if(d<=0)return '';
   var p=li.previousElementSibling;
   while(p&&dep(p)>=d)p=p.previousElementSibling;
   return (p&&dep(p)===d-1)?p.getAttribute('data-id'):'';}
  function setParents(){items.forEach(function(li){
    var pf=li.querySelector('[data-parent-field]');if(!pf)return;
    if(li.classList.contains('mi-entry'))return; /* пункты-записи: родитель не меняется */
    pf.value=parentOf(li);});}
  function refreshOrder(){var i=0;[].forEach.call(tree.querySelectorAll('.menu-item'),function(li){
    var o=li.querySelector('input[name="order[]"]');if(o)o.value=li.getAttribute('data-id');i++;});
    var h=document.getElementById('menu-empty-hint');if(h)h.hidden=i>0;setParents();}
  function indent(li){if(li.classList.contains('mi-entry'))return;var prev=li.previousElementSibling;if(!prev||dep(prev)<dep(li))return;setDepth(li,dep(li)+1);setParents();}
  function outdent(li){if(li.classList.contains('mi-entry'))return;if(dep(li)<=0)return;setDepth(li,dep(li)-1);setParents();}

  items.forEach(function(li){
   setDepth(li,li.getAttribute('data-parent')?1:0);
   li.addEventListener('dragstart',function(ev){ev.dataTransfer.setData('text/plain',li.getAttribute('data-id'));ev.dataTransfer.effectAllowed='move';li.classList.add('mi-dragging');});
   li.addEventListener('dragend',function(){li.classList.remove('mi-dragging');[].forEach.call(tree.querySelectorAll('.mi-drop-before'),function(x){x.classList.remove('mi-drop-before');});});
   var rm=li.querySelector('.mi-remove');
   if(rm)rm.addEventListener('click',function(){if(confirm('Убрать пункт из структуры меню?')){li.parentNode.removeChild(li);refreshOrder();}});
   li.addEventListener('keydown',function(ev){
    if(ev.altKey&&ev.key==='ArrowRight'){ev.preventDefault();indent(li);}
    if(ev.altKey&&ev.key==='ArrowLeft'){ev.preventDefault();outdent(li);}
    if(ev.altKey&&ev.key==='ArrowUp'&&li.previousElementSibling){ev.preventDefault();li.parentNode.insertBefore(li,li.previousElementSibling);refreshOrder();}
    if(ev.altKey&&ev.key==='ArrowDown'&&li.nextElementSibling){ev.preventDefault();li.parentNode.insertBefore(li.nextElementSibling,li);refreshOrder();}
   });
   li.tabIndex=0;
  });
  tree.addEventListener('dragover',function(ev){
   var target=ev.target.closest?ev.target.closest('.menu-item'):null;
   if(!target)return; ev.preventDefault(); ev.dataTransfer.dropEffect='move';
   [].forEach.call(tree.querySelectorAll('.mi-drop-before'),function(x){x.classList.remove('mi-drop-before');});
   target.classList.add('mi-drop-before');
  });
  tree.addEventListener('drop',function(ev){
   var target=ev.target.closest?ev.target.closest('.menu-item'):null;
   if(!target)return; ev.preventDefault();
   var id=ev.dataTransfer.getData('text/plain');var src=byId[id];
   if(!src||src===target)return;
   var rect=target.getBoundingClientRect();
   var after=ev.clientY>rect.top+rect.height/2;
   tree.insertBefore(src,after?target.nextSibling:target);
   target.classList.remove('mi-drop-before');
   if(!src.classList.contains('mi-entry')){
    /* при броске «после» пункт становится соседом target на его уровне;
       при броске «перед» — перед target, глубина не больше глубины target */
    var want=after?dep(target):Math.min(dep(src),dep(target));
    setDepth(src,want);
   }
   refreshOrder();
  });
  refreshOrder();
 }

 /* переключение блоков «Запись сайта / Произвольная ссылка» */
 var addForm=document.querySelector('[data-menu-add]');
 if(addForm){
  var sel=addForm.querySelector('[name="item_type"]');
  function toggleType(){var v=sel.value;[].forEach.call(addForm.querySelectorAll('[data-show]'),function(d){d.hidden=d.getAttribute('data-show')!==v;});}
  sel.addEventListener('change',toggleType);toggleType();
 }

 /* развёрнутые списки в блоке «Структура сайта» */
 [].forEach.call(document.querySelectorAll('.struct-list'),function(list){
  var boxes=[].slice.call(list.querySelectorAll('input[type=checkbox]'));
  if(boxes.length<=6)return;
  boxes.forEach(function(b,i){if(i>5)b.parentNode.style.display='none';});
  var btn=document.createElement('button');btn.type='button';btn.className='link-btn struct-more';btn.textContent='Показать все ('+boxes.length+')';
  btn.addEventListener('click',function(){boxes.forEach(function(b){b.parentNode.style.display='';});btn.parentNode.removeChild(btn);});
  list.appendChild(document.createElement('br'));list.appendChild(btn);
 });
})();
