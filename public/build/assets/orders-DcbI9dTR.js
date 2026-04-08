import{A as e,Et as t,G as n,M as r,P as i,Q as a,S as o,T as s,U as c,W as l,_ as u,ct as d,d as f,f as p,g as m,i as ee,l as h,p as g,rt as _,tt as v,u as y,v as b,w as x}from"./runtime-core.esm-bundler-D-CWpDMB.js";import{F as S,H as C,P as w,Pt as T,U as te,V as ne,Zt as E,b as D,g as O,h as k,m as A,o as j,p as re,q as M,u as N,x as ie,zt as P}from"./ripple-Cl-qSgYx.js";import{n as F,t as I}from"./VOverlay-BmcbB_Hs.js";import{t as L}from"./scopeId-BHOPgWgE.js";import{c as ae,i as oe,l as se,r as R,t as z}from"./VBtn-CsgJP9mT.js";import{t as B}from"./apiFetch-DE4Z1lY0.js";import{c as ce,l as V}from"./main-DxHae-HE.js";import{t as H}from"./VTooltip-ukRPqDvA.js";import{t as U}from"./forwardRefs-_naIgL9m.js";import{t as le}from"./VDivider-DLXlolzz.js";import{n as W,t as G}from"./VRow-DVcSesly.js";import{t as ue}from"./VSpacer-DJV85b_k.js";import{i as K,n as q,o as de,t as J}from"./VCard-3kQZr-ms.js";import{t as fe}from"./VTextField-DuQX_VXx.js";import{t as pe}from"./VAlert-CiG5aAde.js";import{t as Y}from"./VChip-BciJYChs.js";import{t as me}from"./VSelect-DMDhbYjA.js";import{t as he}from"./VTable--329G-S9.js";import{t as ge}from"./VDataTableServer-CZ5EyNyg.js";import{t as _e}from"./VDialog-BUDXLNnP.js";import{t as ve}from"./VTextarea-ChnpbeUK.js";import{t as X}from"./currency-BIyuvi5i.js";function Z(e){let t=_(e()),n=-1;function r(){clearInterval(n)}function i(){r(),s(()=>t.value=e())}function o(i){let a=i?getComputedStyle(i):{transitionDuration:.2},o=parseFloat(a.transitionDuration)*1e3||200;if(r(),t.value<=0)return;let s=performance.now();n=window.setInterval(()=>{let n=performance.now()-s+o;t.value=Math.max(e()-n,0),t.value<=0&&r()},o)}return a(r),{clear:r,time:t,start:o,reset:i}}var Q=E({multiLine:Boolean,text:String,timer:[Boolean,String],timeout:{type:[Number,String],default:5e3},vertical:Boolean,...j({location:`bottom`}),...R(),...D(),...k(),...w(),...T(F({transition:`v-snackbar-transition`}),[`persistent`,`noClickAnimation`,`retainFocus`,`captureFocus`,`disableInitialFocus`,`scrim`,`scrollStrategy`,`stickToTarget`,`viewportMargin`])},`VSnackbar`),ye=M()({name:`VSnackbar`,props:Q(),emits:{"update:modelValue":e=>!0},setup(t,n){let{slots:r}=n,i=ne(t,`modelValue`),{positionClasses:a}=oe(t),{scopeId:s}=L(),{themeClasses:d}=S(t),{colorClasses:f,colorStyles:p,variantClasses:m}=O(t),{roundedClasses:ee}=ie(t),g=Z(()=>Number(t.timeout)),b=v(),w=v(),T=_(!1),E=_(0),D=v(),k=o(ce,void 0);C(()=>!!k,()=>{let e=V();l(()=>{D.value=e.mainStyles.value})}),c(i,M),c(()=>t.timeout,M),e(()=>{i.value&&M()});let j=-1;function M(){g.reset(),window.clearTimeout(j);let e=Number(t.timeout);if(!i.value||e===-1)return;let n=P(w.value);g.start(n),j=window.setTimeout(()=>{i.value=!1},e)}function N(){g.reset(),window.clearTimeout(j)}function F(){T.value=!0,N()}function se(){T.value=!1,M()}function R(e){E.value=e.touches[0].clientY}function z(e){Math.abs(E.value-e.changedTouches[0].clientY)>50&&(i.value=!1)}function B(){T.value&&se()}let H=h(()=>t.location.split(` `).reduce((e,t)=>(e[`v-snackbar--${t}`]=!0,e),{}));return te(()=>{let e=I.filterProps(t),n=!!(r.default||r.text||t.text);return u(I,x({ref:b,class:[`v-snackbar`,{"v-snackbar--active":i.value,"v-snackbar--multi-line":t.multiLine&&!t.vertical,"v-snackbar--timer":!!t.timer,"v-snackbar--vertical":t.vertical},H.value,a.value,t.class],style:[D.value,t.style]},e,{modelValue:i.value,"onUpdate:modelValue":e=>i.value=e,contentProps:x({class:[`v-snackbar__wrapper`,d.value,f.value,ee.value,m.value],style:[p.value],onPointerenter:F,onPointerleave:se},e.contentProps),persistent:!0,noClickAnimation:!0,scrim:!1,scrollStrategy:`none`,_disableGlobalStack:!0,onTouchstartPassive:R,onTouchend:z,onAfterLeave:B},s),{default:()=>[A(!1,`v-snackbar`),t.timer&&!T.value&&y(`div`,{key:`timer`,class:`v-snackbar__timer`},[u(ae,{ref:w,color:typeof t.timer==`string`?t.timer:`info`,max:t.timeout,modelValue:g.time.value},null)]),n&&y(`div`,{key:`content`,class:`v-snackbar__content`,role:`status`,"aria-live":`polite`},[r.text?.()??t.text,r.default?.()]),r.actions&&u(re,{defaults:{VBtn:{variant:`text`,ripple:!1,slim:!0}}},{default:()=>[y(`div`,{class:`v-snackbar__actions`},[r.actions({isActive:i})])]})],activator:r.activator})}),U({},b)}}),be={class:`text-medium-emphasis text-body-2`},xe={class:`font-weight-bold text-primary`},Se={class:`font-weight-medium`},Ce=[`onClick`],we={class:`text-body-2`},Te={class:`font-weight-bold`},Ee={key:0,class:`text-caption text-success`},De={class:`text-body-2`},Oe={class:`d-flex gap-1 justify-center`},ke={class:`text-error`},Ae={class:`d-flex align-center gap-2`},je={class:`d-flex gap-2`},Me={class:`d-flex align-center justify-space-between mb-4`},Ne=[`src`],Pe={key:1,class:`text-h6 font-weight-bold`},Fe={class:`text-body-2 text-medium-emphasis mt-1`},Ie={class:`text-body-2 text-medium-emphasis`},Le={class:`text-end`},Re={class:`text-h6 font-weight-bold text-primary`},ze={class:`text-body-2 text-medium-emphasis`},Be={class:`text-body-2`},Ve={class:`text-body-2`},He={dir:`ltr`,style:{"unicode-bidi":`embed`}},Ue={class:`text-body-2`},We={class:`text-body-2`},Ge={class:`text-body-2`},Ke={class:`text-center`},qe=[`src`],Je={class:`text-caption text-medium-emphasis`},Ye={class:`font-weight-medium`},Xe={class:`d-flex flex-column align-end gap-1 mb-4`},Ze={class:`text-body-2`},Qe={key:0,class:`text-body-2 text-success`},$e={key:0,class:`text-caption`},et={class:`text-h6 font-weight-bold text-primary`},tt={class:`text-center text-body-1 text-medium-emphasis font-italic`},nt=b({__name:`orders`,setup(a){let o=v([]),s=v(!1),l=v(0),h=v(1),_=v(15),b=v(``),S=v(null),C=v(``),w=v(``),T=[{title:`تم الإرسال`,value:`sent`},{title:`تم الاستلام وجاري التجهيز`,value:`received_preparing`},{title:`جاري التوصيل`,value:`out_for_delivery`},{title:`تم التسليم`,value:`delivered`},{title:`تم الرفض`,value:`rejected`},{title:`تم الإلغاء`,value:`cancelled`}],te=e=>({sent:`primary`,received_preparing:`warning`,out_for_delivery:`info`,delivered:`success`,rejected:`error`,cancelled:`default`})[e]||`default`,ne=e=>T.find(t=>t.value===e)?.title??e,E=[{title:`#`,key:`seq`,align:`center`,sortable:!1,width:`60px`},{title:`رقم الفاتورة`,key:`invoice_code`,align:`start`,sortable:!1},{title:`الزبون`,key:`customer`,align:`start`,sortable:!1},{title:`القضاء/المنطقة`,key:`location`,align:`start`,sortable:!1},{title:`المبلغ`,key:`final_amount`,align:`start`,sortable:!1},{title:`التاريخ`,key:`created_at`,align:`start`,sortable:!1},{title:`الحالة`,key:`status`,align:`center`,sortable:!1},{title:`الإجراءات`,key:`actions`,align:`center`,sortable:!1}],D=async(e=1)=>{s.value=!0,h.value=e;try{let t=new URLSearchParams({page:String(e),per_page:String(_.value)});b.value&&t.set(`search`,b.value),S.value&&t.set(`status`,S.value),C.value&&t.set(`date_from`,C.value),w.value&&t.set(`date_to`,w.value);let n=await(await B(`/api/admin/orders?${t}`)).json();o.value=n.data,l.value=n.total}finally{s.value=!1}};c([b,S,C,w],()=>D(1)),e(()=>D(1));let O=v(!1),k=v(null),A=v({}),j=v(!1),re=async e=>{j.value=!0,O.value=!0;try{let t=await(await B(`/api/admin/orders/${e.id}`)).json();k.value=t.order,A.value=t.settings}finally{j.value=!1}},M=()=>{if(!k.value)return;let e=k.value,t=A.value,n=window.location.origin,r=`https://api.qrserver.com/v1/create-qr-code/?size=110x110&data=${encodeURIComponent(ie(e.invoice_token))}`,i=t.logo?`<img src="${n}/storage/${t.logo}" style="max-height:55px;max-width:130px;" />`:`<div style="font-size:15pt;font-weight:700;color:#0d47a1;">معمل امواج ديالى</div>`,a=e.items.map((e,t)=>`
    <tr>
      <td style="text-align:center;color:#888;">${t+1}</td>
      <td style="font-weight:600;">${e.product_name}${e.sku?`<br><span style="font-size:8pt;color:#aaa;">${e.sku}</span>`:``}</td>
      <td style="text-align:center;">${X(e.unit_price)}</td>
      <td style="text-align:center;font-weight:700;">× ${e.quantity}</td>
      <td style="text-align:center;font-weight:700;color:#1b5e20;">${X(e.total_price)}</td>
    </tr>`).join(``),o=parseFloat(e.discount_amount)>0?`<tr style="color:#2e7d32;">
        <td colspan="3" style="text-align:right;padding:5px 8px;">الخصم${e.coupon?` (${e.coupon.code})`:``}</td>
        <td colspan="2" style="text-align:center;padding:5px 8px;font-weight:700;">- ${X(e.discount_amount)}</td>
       </tr>`:`<tr style="color:#aaa;font-style:italic;">
        <td colspan="3" style="text-align:right;padding:5px 8px;">الخصم</td>
        <td colspan="2" style="text-align:center;padding:5px 8px;">لا يوجد خصم</td>
       </tr>`,s=`<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>فاتورة ${e.invoice_code}</title>
<style>
  @page { size: A5 portrait; margin: 8mm; }
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: Tahoma, Arial, sans-serif; color: #111; font-size: 10.5pt; background:#fff; }
  .wrap { padding: 2mm; }

  /* Header */
  .hd { display:flex; justify-content:space-between; align-items:flex-start;
        background:linear-gradient(135deg,#0d47a1,#1565c0 55%,#1b7a3e);
        color:#fff; padding:10px 14px; border-radius:8px 8px 0 0; }
  .hd-brand { display:flex; align-items:center; gap:10px; }
  .hd-brand-name { font-size:13pt; font-weight:700; }
  .hd-brand-sub  { font-size:8pt; color:rgba(255,255,255,.75); margin-top:2px; }
  .hd-meta { text-align:start; }
  .hd-code { font-size:12pt; font-weight:700; font-family:monospace; }
  .hd-date { font-size:8pt; color:rgba(255,255,255,.75); margin-top:3px; }

  /* Info row */
  .info-row { display:flex; gap:10px; padding:10px 14px; border:1px solid #e0e0e0;
              border-top:none; background:#fafafa; }
  .info-tbl { flex:1; border-collapse:collapse; font-size:9.5pt; }
  .info-tbl td { padding:3px 5px; vertical-align:top; }
  .lbl { color:#888; width:110px; white-space:nowrap; }
  .val { font-weight:600; }

  /* Items */
  .sec-title { font-size:9pt; font-weight:700; color:#1565c0;
               border-bottom:2px solid #e3f0ff; padding:6px 14px 4px;
               background:#f5f8ff; }
  .items-tbl { width:100%; border-collapse:collapse; font-size:9.5pt; }
  .items-tbl thead tr { background:#1565c0; color:#fff; }
  .items-tbl thead th { padding:7px 8px; font-size:8.5pt; }
  .items-tbl tbody tr:nth-child(even) { background:#f8faff; }
  .items-tbl tbody td { padding:6px 8px; border-bottom:1px solid #f0f0f0; }

  /* Totals */
  .totals-wrap { display:flex; justify-content:flex-start; padding:10px 14px 0; }
  .totals-tbl { border-collapse:collapse; min-width:240px;
                border:1px solid #e3f0ff; border-radius:8px; overflow:hidden; font-size:9.5pt; }
  .totals-tbl td { padding:5px 12px; }
  .final-row td { font-size:11pt; font-weight:800; color:#1565c0;
                  border-top:2px solid #e3f0ff; }

  /* Footer */
  .foot { background:linear-gradient(135deg,#0d47a1,#1565c0 55%,#1b7a3e);
          color:#fff; text-align:center; padding:9px; margin-top:10px;
          border-radius:0 0 8px 8px; font-size:9pt; }
  .foot-sub { color:rgba(255,255,255,.65); font-size:7.5pt; margin-top:3px; }
</style>
</head>
<body>
<div class="wrap">

  <div class="hd">
    <div class="hd-brand">
      ${i}
      <div>
        <div class="hd-brand-name">معمل امواج ديالى</div>
        <div class="hd-brand-sub">لإنتاج وتعبئة المياه</div>
        ${t.store_phone?`<div class="hd-brand-sub" dir="ltr">${t.store_phone}</div>`:``}
      </div>
    </div>
    <div class="hd-meta">
      <div class="hd-code">${e.invoice_code}</div>
      <div class="hd-date">${it(e.created_at)}</div>
    </div>
  </div>

  <div class="info-row">
    <table class="info-tbl">
      <tr><td class="lbl">الزبون</td><td class="val">${e.customer_name}</td></tr>
      <tr><td class="lbl">الهاتف</td><td class="val" dir="ltr">${e.customer_phone}</td></tr>
      <tr><td class="lbl">المحافظة</td><td class="val">${e.province}</td></tr>
      <tr><td class="lbl">القضاء / المنطقة</td><td class="val">${e.district}</td></tr>
      <tr><td class="lbl">أقرب نقطة دالة</td><td class="val">${e.nearest_landmark||`—`}</td></tr>
      ${e.notes?`<tr><td class="lbl">ملاحظات</td><td class="val">${e.notes}</td></tr>`:``}
    </table>
    <div style="text-align:center;flex-shrink:0;">
      <img src="${r}" width="90" height="90" style="border:2px solid #e0e0e0;border-radius:6px;" />
      <div style="font-size:7pt;color:#aaa;margin-top:3px;">مسح للفاتورة</div>
    </div>
  </div>

  <div class="sec-title">تفاصيل الطلب</div>
  <table class="items-tbl">
    <thead>
      <tr>
        <th style="width:30px;">#</th>
        <th style="text-align:right;">المنتج</th>
        <th style="width:90px;">سعر الوحدة</th>
        <th style="width:55px;">الكمية</th>
        <th style="width:100px;">المجموع</th>
      </tr>
    </thead>
    <tbody>${a}</tbody>
  </table>

  <div class="totals-wrap">
    <table class="totals-tbl">
      <tr><td>المجموع</td><td style="font-weight:600;text-align:center;">${X(e.total_amount)}</td></tr>
      ${o.replace(/<tr[^>]*>|<\/tr>|<td[^>]*>|<\/td>/g,e=>({'<tr style="color:#2e7d32;">':`<tr>`,'<tr style="color:#aaa;font-style:italic;">':`<tr style="color:#aaa;font-style:italic;">`})[e]??e)}
      <tr class="final-row"><td>الإجمالي النهائي</td><td style="text-align:center;">${X(e.final_amount)}</td></tr>
    </table>
  </div>

  <div class="foot">
    <div>${t.thank_you_message||`شكراً لثقتكم بمعمل امواج ديالى`}</div>
    <div class="foot-sub">معمل امواج ديالى — ديالى، العراق</div>
  </div>

</div>
</body>
</html>`,c=window.open(``,`_blank`);if(!c){alert(`يرجى السماح للنوافذ المنبثقة في المتصفح`);return}c.document.write(s),c.document.close(),c.focus(),c.print()},ie=e=>`${window.location.origin}/invoice/${e}`,P=v(!1),F=v(null),I=v(``),L=v(!1),ae=e=>{F.value=e.id,I.value=e.invoice_code,P.value=!0},oe=async()=>{L.value=!0;try{await B(`/api/admin/orders/${F.value}`,{method:`DELETE`}),P.value=!1,await D(h.value)}finally{L.value=!1}},R=v(!1),ce=v(null),V=v(``),U=v(``),Z=v(!1),Q=v(``),nt=e=>{ce.value=e.id,V.value=e.status,U.value=e.rejection_reason??``,Q.value=``,R.value=!0},rt=async()=>{Z.value=!0,Q.value=``;try{let e=await B(`/api/admin/orders/${ce.value}/status`,{method:`PATCH`,body:JSON.stringify({status:V.value,rejection_reason:U.value})});if(!e.ok){Q.value=(await e.json()).message??`حدث خطأ`;return}R.value=!1,await D(h.value)}finally{Z.value=!1}},it=e=>new Date(e).toLocaleString(`ar-IQ`,{dateStyle:`short`,timeStyle:`short`}),at=e=>(h.value-1)*_.value+e+1,$=v(!1),ot=async e=>{try{await navigator.clipboard.writeText(e),$.value=!0}catch{let t=document.createElement(`textarea`);t.value=e,document.body.appendChild(t),t.select(),document.execCommand(`copy`),document.body.removeChild(t),$.value=!0}};return(e,a)=>(r(),g(`div`,null,[u(G,null,{default:n(()=>[u(W,{cols:`12`},{default:n(()=>[u(J,null,{default:n(()=>[u(K,{class:`pa-4 d-flex align-center justify-space-between flex-wrap gap-2`},{default:n(()=>[a[14]||=y(`span`,null,`إدارة الطلبات`,-1),u(Y,{color:`primary`,variant:`tonal`},{default:n(()=>[m(t(l.value)+` طلب`,1)]),_:1})]),_:1}),u(q,{class:`pb-0`},{default:n(()=>[u(G,{dense:``},{default:n(()=>[u(W,{cols:`12`,md:`4`},{default:n(()=>[u(fe,{modelValue:b.value,"onUpdate:modelValue":a[0]||=e=>b.value=e,"prepend-inner-icon":`ri-search-line`,placeholder:`رقم الفاتورة أو هاتف الزبون...`,density:`compact`,clearable:``,"hide-details":``,variant:`outlined`},null,8,[`modelValue`])]),_:1}),u(W,{cols:`6`,md:`2`},{default:n(()=>[u(me,{modelValue:S.value,"onUpdate:modelValue":a[1]||=e=>S.value=e,items:T,"item-title":`title`,"item-value":`value`,placeholder:`الحالة`,density:`compact`,clearable:``,"hide-details":``,variant:`outlined`},null,8,[`modelValue`])]),_:1}),u(W,{cols:`6`,md:`2`},{default:n(()=>[u(fe,{modelValue:C.value,"onUpdate:modelValue":a[2]||=e=>C.value=e,type:`date`,label:`من`,density:`compact`,"hide-details":``,variant:`outlined`},null,8,[`modelValue`])]),_:1}),u(W,{cols:`6`,md:`2`},{default:n(()=>[u(fe,{modelValue:w.value,"onUpdate:modelValue":a[3]||=e=>w.value=e,type:`date`,label:`إلى`,density:`compact`,"hide-details":``,variant:`outlined`},null,8,[`modelValue`])]),_:1})]),_:1})]),_:1}),u(ge,{headers:E,items:o.value,"items-length":l.value,loading:s.value,"items-per-page":_.value,"onUpdate:options":a[4]||=e=>D(e.page),class:`mt-2`},{"item.seq":n(({index:e})=>[y(`span`,be,t(at(e)),1)]),"item.invoice_code":n(({item:e})=>[y(`span`,xe,t(e.invoice_code),1)]),"item.customer":n(({item:e})=>[y(`div`,null,[y(`div`,Se,t(e.customer_name),1),y(`div`,{class:`text-caption text-primary d-flex align-center gap-1 cursor-pointer`,dir:`ltr`,style:{"text-align":`right`,width:`fit-content`},title:`اضغط لنسخ رقم الهاتف`,onClick:t=>ot(e.customer_phone)},[u(N,{size:`12`,icon:`ri-file-copy-line`}),m(` `+t(e.customer_phone),1)],8,Ce)])]),"item.location":n(({item:e})=>[y(`div`,we,t(e.province)+` / `+t(e.district),1)]),"item.final_amount":n(({item:e})=>[y(`div`,null,[y(`div`,Te,t(d(X)(e.final_amount)),1),parseFloat(e.discount_amount)>0?(r(),g(`div`,Ee,` خصم: -`+t(d(X)(e.discount_amount)),1)):p(``,!0)])]),"item.created_at":n(({item:e})=>[y(`span`,De,t(it(e.created_at)),1)]),"item.status":n(({item:e})=>[u(Y,{color:te(e.status),size:`small`,variant:`tonal`},{default:n(()=>[m(t(ne(e.status)),1)]),_:2},1032,[`color`])]),"item.actions":n(({item:e})=>[y(`div`,Oe,[u(H,{text:`عرض الفاتورة`},{activator:n(({props:t})=>[u(z,x(t,{icon:``,size:`small`,variant:`text`,color:`info`,onClick:t=>re(e)}),{default:n(()=>[u(N,null,{default:n(()=>[...a[15]||=[m(`ri-eye-line`,-1)]]),_:1})]),_:1},16,[`onClick`])]),_:2},1024),u(H,{text:`تغيير الحالة`},{activator:n(({props:t})=>[u(z,x(t,{icon:``,size:`small`,variant:`text`,color:`warning`,onClick:t=>nt(e)}),{default:n(()=>[u(N,null,{default:n(()=>[...a[16]||=[m(`ri-refresh-line`,-1)]]),_:1})]),_:1},16,[`onClick`])]),_:2},1024),u(H,{text:`حذف الطلب`},{activator:n(({props:t})=>[u(z,x(t,{icon:``,size:`small`,variant:`text`,color:`error`,onClick:t=>ae(e)}),{default:n(()=>[u(N,null,{default:n(()=>[...a[17]||=[m(`ri-delete-bin-line`,-1)]]),_:1})]),_:1},16,[`onClick`])]),_:2},1024)])]),_:1},8,[`items`,`items-length`,`loading`,`items-per-page`])]),_:1})]),_:1})]),_:1}),u(_e,{modelValue:P.value,"onUpdate:modelValue":a[6]||=e=>P.value=e,"max-width":`380`},{default:n(()=>[u(J,null,{default:n(()=>[u(K,{class:`pa-4 d-flex align-center gap-2`},{default:n(()=>[u(N,{color:`error`,size:`24`},{default:n(()=>[...a[18]||=[m(`ri-error-warning-line`,-1)]]),_:1}),a[19]||=m(` تأكيد الحذف `,-1)]),_:1}),u(q,null,{default:n(()=>[a[20]||=m(` هل تريد حذف الطلب `,-1),y(`strong`,ke,t(I.value),1),a[21]||=m(` نهائياً؟ `,-1),a[22]||=y(`br`,null,null,-1),a[23]||=y(`span`,{class:`text-caption text-medium-emphasis`},`لا يمكن التراجع عن هذا الإجراء.`,-1)]),_:1}),u(de,null,{default:n(()=>[u(ue),u(z,{variant:`text`,onClick:a[5]||=e=>P.value=!1},{default:n(()=>[...a[24]||=[m(`إلغاء`,-1)]]),_:1}),u(z,{color:`error`,loading:L.value,onClick:oe},{default:n(()=>[...a[25]||=[m(`حذف`,-1)]]),_:1},8,[`loading`])]),_:1})]),_:1})]),_:1},8,[`modelValue`]),u(_e,{modelValue:R.value,"onUpdate:modelValue":a[10]||=e=>R.value=e,"max-width":`420`},{default:n(()=>[u(J,{title:`تغيير حالة الطلب`},{default:n(()=>[u(q,null,{default:n(()=>[u(me,{modelValue:V.value,"onUpdate:modelValue":a[7]||=e=>V.value=e,items:T,"item-title":`title`,"item-value":`value`,label:`الحالة الجديدة`,variant:`outlined`},null,8,[`modelValue`]),V.value===`rejected`?(r(),f(ve,{key:0,modelValue:U.value,"onUpdate:modelValue":a[8]||=e=>U.value=e,label:`سبب الرفض (اختياري)`,rows:`2`,variant:`outlined`,class:`mt-3`},null,8,[`modelValue`])):p(``,!0),Q.value?(r(),f(pe,{key:1,type:`error`,class:`mt-2`,variant:`tonal`},{default:n(()=>[m(t(Q.value),1)]),_:1})):p(``,!0)]),_:1}),u(de,null,{default:n(()=>[u(ue),u(z,{variant:`text`,onClick:a[9]||=e=>R.value=!1},{default:n(()=>[...a[26]||=[m(`إلغاء`,-1)]]),_:1}),u(z,{color:`primary`,loading:Z.value,onClick:rt},{default:n(()=>[...a[27]||=[m(`حفظ`,-1)]]),_:1},8,[`loading`])]),_:1})]),_:1})]),_:1},8,[`modelValue`]),u(_e,{modelValue:O.value,"onUpdate:modelValue":a[12]||=e=>O.value=e,"max-width":`780`,scrollable:``},{default:n(()=>[u(J,null,{default:n(()=>[u(K,{class:`pa-4 d-flex align-center justify-space-between no-print`},{default:n(()=>[y(`span`,Ae,[u(N,{icon:`ri-file-text-line`,color:`primary`}),m(` `+t(k.value?.invoice_code||`الفاتورة`),1)]),y(`div`,je,[u(z,{color:`primary`,variant:`elevated`,rounded:`lg`,"prepend-icon":`ri-printer-line`,onClick:M},{default:n(()=>[...a[28]||=[m(` طباعة / PDF `,-1)]]),_:1}),u(z,{icon:``,variant:`text`,onClick:a[11]||=e=>O.value=!1},{default:n(()=>[u(N,null,{default:n(()=>[...a[29]||=[m(`ri-close-line`,-1)]]),_:1})]),_:1})])]),_:1}),j.value?(r(),f(q,{key:0,class:`text-center pa-8`},{default:n(()=>[u(se,{indeterminate:``,color:`primary`})]),_:1})):k.value?(r(),f(q,{key:1,id:`invoice-print-area`,class:`pa-6`},{default:n(()=>[y(`div`,Me,[y(`div`,null,[A.value.logo?(r(),g(`img`,{key:0,src:`/storage/${A.value.logo}`,style:{"max-height":`70px`,"max-width":`160px`}},null,8,Ne)):(r(),g(`div`,Pe,t(A.value.store_name||`المتجر`),1)),y(`div`,Fe,t(A.value.store_phone),1),y(`div`,Ie,t(A.value.store_address),1)]),y(`div`,Le,[y(`div`,Re,t(k.value.invoice_code),1),y(`div`,ze,t(it(k.value.created_at)),1),u(Y,{color:te(k.value.status),size:`small`,variant:`tonal`,class:`mt-1`},{default:n(()=>[m(t(ne(k.value.status)),1)]),_:1},8,[`color`])])]),u(le,{class:`mb-4`}),u(G,{class:`mb-4`},{default:n(()=>[u(W,{cols:`12`,md:`6`},{default:n(()=>[a[36]||=y(`div`,{class:`text-subtitle-2 font-weight-bold mb-2`},`بيانات الزبون`,-1),y(`div`,Be,[a[30]||=y(`span`,{class:`font-weight-medium`},`الاسم:`,-1),m(` `+t(k.value.customer_name),1)]),y(`div`,Ve,[a[31]||=y(`span`,{class:`font-weight-medium`},`الهاتف:`,-1),a[32]||=m(),y(`span`,He,t(k.value.customer_phone),1)]),y(`div`,Ue,[a[33]||=y(`span`,{class:`font-weight-medium`},`القضاء:`,-1),m(` `+t(k.value.province),1)]),y(`div`,We,[a[34]||=y(`span`,{class:`font-weight-medium`},`المنطقة:`,-1),m(` `+t(k.value.district),1)]),y(`div`,Ge,[a[35]||=y(`span`,{class:`font-weight-medium`},`أقرب نقطة دالة:`,-1),m(` `+t(k.value.nearest_landmark||`—`),1)])]),_:1}),u(W,{cols:`12`,md:`6`,class:`d-flex justify-end align-start`},{default:n(()=>[y(`div`,Ke,[y(`img`,{src:`https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=${encodeURIComponent(ie(k.value.invoice_token))}`,width:`100`,height:`100`,alt:`QR`,style:{border:`1px solid #eee`,"border-radius":`6px`}},null,8,qe),a[37]||=y(`div`,{class:`text-caption text-medium-emphasis mt-1`},`مسح للفاتورة`,-1)])]),_:1})]),_:1}),a[41]||=y(`div`,{class:`text-subtitle-2 font-weight-bold mb-2`},`المنتجات`,-1),u(he,{density:`compact`,class:`mb-4`},{default:n(()=>[a[38]||=y(`thead`,null,[y(`tr`,null,[y(`th`,null,`#`),y(`th`,null,`المنتج`),y(`th`,null,`SKU`),y(`th`,null,`السعر`),y(`th`,null,`الكمية`),y(`th`,null,`المجموع`)])],-1),y(`tbody`,null,[(r(!0),g(ee,null,i(k.value.items,(e,n)=>(r(),g(`tr`,{key:e.id},[y(`td`,null,t(n+1),1),y(`td`,null,t(e.product_name),1),y(`td`,Je,t(e.sku??`—`),1),y(`td`,null,t(d(X)(e.unit_price)),1),y(`td`,null,t(e.quantity),1),y(`td`,Ye,t(d(X)(e.total_price)),1)]))),128))])]),_:1}),y(`div`,Xe,[y(`div`,Ze,[a[39]||=m(`المجموع: `,-1),y(`strong`,null,t(d(X)(k.value.total_amount)),1)]),parseFloat(k.value.discount_amount)>0?(r(),g(`div`,Qe,[a[40]||=m(` الخصم: `,-1),y(`strong`,null,`-`+t(d(X)(k.value.discount_amount)),1),k.value.coupon?(r(),g(`span`,$e,` (`+t(k.value.coupon.code)+`)`,1)):p(``,!0)])):p(``,!0),y(`div`,et,`الإجمالي: `+t(d(X)(k.value.final_amount)),1)]),u(le,{class:`mb-4`}),y(`div`,tt,t(A.value.thank_you_message||`شكراً لثقتكم بنا`),1)]),_:1})):p(``,!0)]),_:1})]),_:1},8,[`modelValue`]),u(ye,{modelValue:$.value,"onUpdate:modelValue":a[13]||=e=>$.value=e,timeout:2e3,color:`success`,location:`bottom center`,rounded:`lg`},{default:n(()=>[u(N,{icon:`ri-check-line`,class:`me-2`}),a[42]||=m(` تم نسخ رقم الهاتف ✓ `,-1)]),_:1},8,[`modelValue`])]))}});export{nt as default};