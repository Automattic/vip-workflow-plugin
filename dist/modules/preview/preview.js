(()=>{"use strict";var e={6772:(e,t,o)=>{o.d(t,{A:()=>i});var r=o(5573),n=o(4848);const i=(0,n.jsx)(r.SVG,{xmlns:"http://www.w3.org/2000/svg",viewBox:"0 0 24 24",children:(0,n.jsx)(r.Path,{fillRule:"evenodd",clipRule:"evenodd",d:"M5.625 5.5h9.75c.069 0 .125.056.125.125v9.75a.125.125 0 0 1-.125.125h-9.75a.125.125 0 0 1-.125-.125v-9.75c0-.069.056-.125.125-.125ZM4 5.625C4 4.728 4.728 4 5.625 4h9.75C16.273 4 17 4.728 17 5.625v9.75c0 .898-.727 1.625-1.625 1.625h-9.75A1.625 1.625 0 0 1 4 15.375v-9.75Zm14.5 11.656v-9H20v9C20 18.8 18.77 20 17.251 20H6.25v-1.5h11.001c.69 0 1.249-.528 1.249-1.219Z"})})},7334:(e,t,o)=>{o.d(t,{A:()=>s});var r=o(1609),n=o(6427),i=o(9491),l=o(6087);function s(e){const{asyncFunction:t,onCopied:o,children:s,...a}=e,[p,c]=(0,l.useState)(null),w=(0,l.useRef)(null),u=(0,i.useCopyToClipboard)(p,(()=>{o(p)}));return(0,l.useLayoutEffect)((()=>{p&&w.current?.click()}),[p]),(0,r.createElement)(r.Fragment,null,(0,r.createElement)(n.Button,{...a,onClick:async()=>{const e=await t();c(e)}},s),(0,r.createElement)(n.Button,{style:{display:"none"},ref:(0,i.useMergeRefs)([w,u])}))}},1020:(e,t,o)=>{var r=o(1609),n=Symbol.for("react.element"),i=(Symbol.for("react.fragment"),Object.prototype.hasOwnProperty),l=r.__SECRET_INTERNALS_DO_NOT_USE_OR_YOU_WILL_BE_FIRED.ReactCurrentOwner,s={key:!0,ref:!0,__self:!0,__source:!0};t.jsx=function(e,t,o){var r,a={},p=null,c=null;for(r in void 0!==o&&(p=""+o),void 0!==t.key&&(p=""+t.key),void 0!==t.ref&&(c=t.ref),t)i.call(t,r)&&!s.hasOwnProperty(r)&&(a[r]=t[r]);if(e&&e.defaultProps)for(r in t=e.defaultProps)void 0===a[r]&&(a[r]=t[r]);return{$$typeof:n,type:e,key:p,ref:c,props:a,_owner:l.current}}},4848:(e,t,o)=>{e.exports=o(1020)},1609:e=>{e.exports=window.React},1455:e=>{e.exports=window.wp.apiFetch},4715:e=>{e.exports=window.wp.blockEditor},6427:e=>{e.exports=window.wp.components},9491:e=>{e.exports=window.wp.compose},7143:e=>{e.exports=window.wp.data},4309:e=>{e.exports=window.wp.editPost},6087:e=>{e.exports=window.wp.element},7723:e=>{e.exports=window.wp.i18n},692:e=>{e.exports=window.wp.notices},2279:e=>{e.exports=window.wp.plugins},5573:e=>{e.exports=window.wp.primitives}},t={};function o(r){var n=t[r];if(void 0!==n)return n.exports;var i=t[r]={exports:{}};return e[r](i,i.exports,o),i.exports}o.n=e=>{var t=e&&e.__esModule?()=>e.default:()=>e;return o.d(t,{a:t}),t},o.d=(e,t)=>{for(var r in t)o.o(t,r)&&!o.o(e,r)&&Object.defineProperty(e,r,{enumerable:!0,get:t[r]})},o.o=(e,t)=>Object.prototype.hasOwnProperty.call(e,t),(()=>{var e=o(1609),t=o(1455),r=o.n(t),n=o(4715),i=o(6427),l=o(9491),s=o(7143),a=o(4309),p=o(6087),c=o(7723),w=o(6772),u=o(692),d=o(2279),v=o(7334);const m=({onUrl:t,onCloseModal:o})=>{const[n,l]=(0,p.useState)(!1),[a,w]=(0,p.useState)(!1),d=VW_PREVIEW.expiration_options,m=d.find((e=>!0===e.default))?.value||d?.[0]?.value,[f,_]=(0,p.useState)(m),E=d.map((({label:e,value:t})=>({label:e,value:t})));return(0,e.createElement)(i.Modal,{title:"Generate preview link",size:"medium",onRequestClose:o},(0,e.createElement)(i.SelectControl,{label:(0,c.__)("Link expiration","vip-workflow"),value:f,onChange:_,options:E}),(0,e.createElement)(i.CheckboxControl,{className:"vip-workflow-one-time-use-checkbox",label:(0,c.__)("One-time use","vip-workflow"),help:(0,c.__)("The link will expire after one visit.","vip-workflow"),checked:a,onChange:()=>w((e=>!e))}),(0,e.createElement)(i.Flex,{justify:"flex-end"},n&&(0,e.createElement)(i.Spinner,null),(0,e.createElement)(v.A,{variant:"primary",asyncFunction:async()=>{let e={};try{l(!0),e=await r()({url:VW_PREVIEW.url_generate_preview,method:"POST",data:{expiration:f,is_one_time_use:a}})}catch(e){const t=VW_PREVIEW.text_preview_error+" "+e.message;(0,s.dispatch)(u.store).createErrorNotice(t,{id:"vw-preview",isDismissible:!0})}finally{l(!1)}if(e?.url)return e.url},onCopied:e=>{(0,s.dispatch)(u.store).createNotice("info",(0,c.__)("Link copied to clipboard."),{isDismissible:!0,type:"snackbar"}),o(),t(e)}},(0,c.__)("Copy Link","vip-workflow"))))},f=({url:t})=>{const o=(0,p.useRef)(null),r=(0,l.useCopyToClipboard)(t,(()=>{(0,s.dispatch)(u.store).createNotice("info",(0,c.__)("Link copied to clipboard."),{isDismissible:!0,type:"snackbar"})})),a={anchorRef:o,placement:"left-start",offset:36,shift:!0},d="/"+t.split("/").pop();return(0,e.createElement)("div",{className:"vip-workflow-preview-dropdown",ref:o},(0,e.createElement)(i.Dropdown,{popoverProps:a,focusOnMount:!0,renderToggle:({onToggle:t})=>(0,e.createElement)(i.Button,{size:"compact",variant:"tertiary",onClick:t},(0,e.createElement)(i.__experimentalTruncate,{limit:15,ellipsizeMode:"tail"},d)),renderContent:({onClose:o})=>(0,e.createElement)("div",{className:"vip-workflow-preview-dropdown-content"},(0,e.createElement)(n.__experimentalInspectorPopoverHeader,{title:(0,c.__)("Preview Link"),onClose:o}),(0,e.createElement)(i.ExternalLink,{className:"editor-post-url__link",href:t,target:"_blank"},t))}),(0,e.createElement)(i.Button,{icon:w.A,label:(0,c.sprintf)(
// Translators: %s is a placeholder for the link URL, e.g. "Copy link: https://my-site.com/?p=123".
// Translators: %s is a placeholder for the link URL, e.g. "Copy link: https://my-site.com/?p=123".
(0,c.__)("Copy link: %s"),t),ref:r,size:"compact"}))},_=(0,l.compose)((0,s.withSelect)((e=>{const{getEditedPostAttribute:t,getCurrentPostType:o,getCurrentPost:r}=e(a.store),n=r(),i="auto-draft"===n?.status;return{status:t("status"),postType:o(),isUnsavedPost:i}})))((({status:t,postType:o,isUnsavedPost:r})=>{const[n,l]=(0,p.useState)(!1),[s,w]=(0,p.useState)(null),u=(0,p.useMemo)((()=>VW_PREVIEW.custom_status_slugs.includes(t)&&VW_PREVIEW.custom_post_types.includes(o)&&!r),[t,o,r]);return(0,e.createElement)(e.Fragment,null,u&&(0,e.createElement)(a.PluginPostStatusInfo,{className:"vip-workflow-preview"},(0,e.createElement)("div",{className:"vip-workflow-preview-row"},(0,e.createElement)("div",{className:"vip-workflow-preview-label"},(0,c.__)("Preview","vip-workflow")),!s&&(0,e.createElement)(i.Button,{className:"vip-workflow-preview-button",variant:"tertiary",size:"compact",onClick:()=>{l(!0)}},(0,c.__)("Generate Link","vip-workflow")),n&&(0,e.createElement)(m,{onUrl:w,onCloseModal:()=>l(!1)}),s&&(0,e.createElement)(f,{url:s}))))}));(0,d.registerPlugin)("vip-workflow-preview",{icon:"vip-workflow",render:_})})()})();
//# sourceMappingURL=preview.js.map