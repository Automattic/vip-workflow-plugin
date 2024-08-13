(()=>{"use strict";var e={6772:(e,t,r)=>{r.d(t,{A:()=>s});var o=r(5573),n=r(4848);const s=(0,n.jsx)(o.SVG,{xmlns:"http://www.w3.org/2000/svg",viewBox:"0 0 24 24",children:(0,n.jsx)(o.Path,{fillRule:"evenodd",clipRule:"evenodd",d:"M5.625 5.5h9.75c.069 0 .125.056.125.125v9.75a.125.125 0 0 1-.125.125h-9.75a.125.125 0 0 1-.125-.125v-9.75c0-.069.056-.125.125-.125ZM4 5.625C4 4.728 4.728 4 5.625 4h9.75C16.273 4 17 4.728 17 5.625v9.75c0 .898-.727 1.625-1.625 1.625h-9.75A1.625 1.625 0 0 1 4 15.375v-9.75Zm14.5 11.656v-9H20v9C20 18.8 18.77 20 17.251 20H6.25v-1.5h11.001c.69 0 1.249-.528 1.249-1.219Z"})})},1020:(e,t,r)=>{var o=r(1609),n=Symbol.for("react.element"),s=(Symbol.for("react.fragment"),Object.prototype.hasOwnProperty),i=o.__SECRET_INTERNALS_DO_NOT_USE_OR_YOU_WILL_BE_FIRED.ReactCurrentOwner,l={key:!0,ref:!0,__self:!0,__source:!0};t.jsx=function(e,t,r){var o,p={},a=null,c=null;for(o in void 0!==r&&(a=""+r),void 0!==t.key&&(a=""+t.key),void 0!==t.ref&&(c=t.ref),t)s.call(t,o)&&!l.hasOwnProperty(o)&&(p[o]=t[o]);if(e&&e.defaultProps)for(o in t=e.defaultProps)void 0===p[o]&&(p[o]=t[o]);return{$$typeof:n,type:e,key:a,ref:c,props:p,_owner:i.current}}},4848:(e,t,r)=>{e.exports=r(1020)},1609:e=>{e.exports=window.React},1455:e=>{e.exports=window.wp.apiFetch},4715:e=>{e.exports=window.wp.blockEditor},6427:e=>{e.exports=window.wp.components},9491:e=>{e.exports=window.wp.compose},7143:e=>{e.exports=window.wp.data},4309:e=>{e.exports=window.wp.editPost},3656:e=>{e.exports=window.wp.editor},6087:e=>{e.exports=window.wp.element},7723:e=>{e.exports=window.wp.i18n},692:e=>{e.exports=window.wp.notices},2279:e=>{e.exports=window.wp.plugins},5573:e=>{e.exports=window.wp.primitives}},t={};function r(o){var n=t[o];if(void 0!==n)return n.exports;var s=t[o]={exports:{}};return e[o](s,s.exports,r),s.exports}r.n=e=>{var t=e&&e.__esModule?()=>e.default:()=>e;return r.d(t,{a:t}),t},r.d=(e,t)=>{for(var o in t)r.o(t,o)&&!r.o(e,o)&&Object.defineProperty(e,o,{enumerable:!0,get:t[o]})},r.o=(e,t)=>Object.prototype.hasOwnProperty.call(e,t),(()=>{var e=r(1609),t=r(1455),o=r.n(t),n=r(4715),s=r(6427),i=r(9491),l=r(7143),p=r(4309),a=r(3656),c=r(6087),w=r(7723),u=r(6772),d=r(692),v=r(2279);const m=({onUrl:t})=>{const[r,n]=(0,c.useState)(!1);return(0,e.createElement)(e.Fragment,null,r&&(0,e.createElement)(s.Spinner,null),!r&&(0,e.createElement)(s.Button,{className:"vip-workflow-secure-preview-button",variant:"secondary",onClick:async()=>{let e={};try{n(!0),e=await o()({url:VW_SECURE_PREVIEW.url_generate_preview,method:"POST"})}catch(e){const t=VW_SECURE_PREVIEW.text_preview_error+" "+e.message;(0,l.dispatch)(d.store).createErrorNotice(t,{id:"vw-secure-preview",isDismissible:!0})}finally{n(!1)}e?.url&&t(e.url)}},(0,w.__)("Generate Link","vip-workflow")))},_=({url:t})=>{const r=(0,c.useRef)(null),o=(0,i.useCopyToClipboard)(t,(()=>{(0,l.dispatch)(d.store).createNotice("info",(0,w.__)("Link copied to clipboard."),{isDismissible:!0,type:"snackbar"})})),p={anchorRef:r,placement:"left-start",offset:36,shift:!0},a="/"+t.split("/").pop();return(0,e.createElement)("div",{className:"vip-workflow-secure-preview-dropdown",ref:r},(0,e.createElement)(s.Dropdown,{popoverProps:p,focusOnMount:!0,renderToggle:({onToggle:t})=>(0,e.createElement)(s.Button,{size:"compact",variant:"tertiary",onClick:t},(0,e.createElement)(s.__experimentalTruncate,{limit:15,ellipsizeMode:"tail"},a)),renderContent:({onClose:r})=>(0,e.createElement)("div",{className:"vip-workflow-secure-link-dropdown-content"},(0,e.createElement)(n.__experimentalInspectorPopoverHeader,{title:(0,w.__)("Secure Preview Link"),onClose:r}),(0,e.createElement)(s.ExternalLink,{className:"editor-post-url__link",href:t,target:"_blank"},t))}),(0,e.createElement)(s.Button,{icon:u.A,label:(0,w.sprintf)(
// Translators: %s is a placeholder for the link URL, e.g. "Copy link: https://example.com".
// Translators: %s is a placeholder for the link URL, e.g. "Copy link: https://example.com".
(0,w.__)("Copy link: %s"),t),ref:o,size:"compact"}))},f=(0,i.compose)((0,l.withSelect)((e=>({status:e(a.store).getEditedPostAttribute("status"),postType:e(a.store).getCurrentPostType()}))))((({status:t,postType:r})=>{const[o,n]=(0,c.useState)(null);console.log("Post properties:",{status:t,postType:r});const s=(0,c.useMemo)((()=>VW_SECURE_PREVIEW.custom_status_slugs.includes(t)&&VW_SECURE_PREVIEW.custom_post_types.includes(r)),[t,r]);return(0,e.createElement)(e.Fragment,null,s&&(0,e.createElement)(p.PluginPostStatusInfo,{className:"vip-workflow-secure-preview"},(0,e.createElement)("div",{className:"vip-workflow-secure-preview-row"},(0,e.createElement)("div",{className:"vip-workflow-secure-preview-label"},(0,w.__)("Secure Preview","vip-workflow")),!o&&(0,e.createElement)(m,{onUrl:e=>n(e)}),o&&(0,e.createElement)(_,{url:o}))))}));(0,v.registerPlugin)("vip-workflow-secure-preview",{icon:"vip-workflow",render:f})})()})();
//# sourceMappingURL=secure-preview.js.map