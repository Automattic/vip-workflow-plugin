(()=>{"use strict";var e={4764:(e,t,s)=>{s.d(t,{M_:()=>r.M});var r=s(9406)},7805:(e,t,s)=>{s.r(t),s.d(t,{closeModal:()=>g,disableComplementaryArea:()=>l,enableComplementaryArea:()=>c,openModal:()=>v,pinItem:()=>u,setDefaultComplementaryArea:()=>i,setFeatureDefaults:()=>f,setFeatureValue:()=>m,toggleFeature:()=>d,unpinItem:()=>p});var r=s(4040),n=s.n(r),o=s(1233),a=s(9561);const i=(e,t)=>({type:"SET_DEFAULT_COMPLEMENTARY_AREA",scope:e=(0,a.F)(e),area:t=(0,a.M)(e,t)}),c=(e,t)=>({registry:s,dispatch:r})=>{t&&(e=(0,a.F)(e),t=(0,a.M)(e,t),s.select(o.store).get(e,"isComplementaryAreaVisible")||s.dispatch(o.store).set(e,"isComplementaryAreaVisible",!0),r({type:"ENABLE_COMPLEMENTARY_AREA",scope:e,area:t}))},l=e=>({registry:t})=>{e=(0,a.F)(e),t.select(o.store).get(e,"isComplementaryAreaVisible")&&t.dispatch(o.store).set(e,"isComplementaryAreaVisible",!1)},u=(e,t)=>({registry:s})=>{if(!t)return;e=(0,a.F)(e),t=(0,a.M)(e,t);const r=s.select(o.store).get(e,"pinnedItems");!0!==r?.[t]&&s.dispatch(o.store).set(e,"pinnedItems",{...r,[t]:!0})},p=(e,t)=>({registry:s})=>{if(!t)return;e=(0,a.F)(e),t=(0,a.M)(e,t);const r=s.select(o.store).get(e,"pinnedItems");s.dispatch(o.store).set(e,"pinnedItems",{...r,[t]:!1})};function d(e,t){return function({registry:s}){n()("dispatch( 'core/interface' ).toggleFeature",{since:"6.0",alternative:"dispatch( 'core/preferences' ).toggle"}),s.dispatch(o.store).toggle(e,t)}}function m(e,t,s){return function({registry:r}){n()("dispatch( 'core/interface' ).setFeatureValue",{since:"6.0",alternative:"dispatch( 'core/preferences' ).set"}),r.dispatch(o.store).set(e,t,!!s)}}function f(e,t){return function({registry:s}){n()("dispatch( 'core/interface' ).setFeatureDefaults",{since:"6.0",alternative:"dispatch( 'core/preferences' ).setDefaults"}),s.dispatch(o.store).setDefaults(e,t)}}function v(e){return{type:"OPEN_MODAL",name:e}}function g(){return{type:"CLOSE_MODAL"}}},8991:(e,t,s)=>{s.d(t,{E:()=>r});const r="core/interface"},9561:(e,t,s)=>{s.d(t,{F:()=>o,M:()=>a});var r=s(4040),n=s.n(r);function o(e){return["core/edit-post","core/edit-site"].includes(e)?(n()(`${e} interface scope`,{alternative:"core interface scope",hint:"core/edit-post and core/edit-site are merging.",version:"6.6"}),"core"):e}function a(e,t){return"core"===e&&"edit-site/template"===t?(n()("edit-site/template sidebar",{alternative:"edit-post/document",version:"6.6"}),"edit-post/document"):"core"===e&&"edit-site/block-inspector"===t?(n()("edit-site/block-inspector sidebar",{alternative:"edit-post/block",version:"6.6"}),"edit-post/block"):t}},9406:(e,t,s)=>{s.d(t,{M:()=>c});var r=s(7143),n=s(7805),o=s(532),a=s(4962),i=s(8991);const c=(0,r.createReduxStore)(i.E,{reducer:a.Ay,actions:n,selectors:o});(0,r.register)(c)},4962:(e,t,s)=>{s.d(t,{Ay:()=>r});const r=(0,s(7143).combineReducers)({complementaryAreas:function(e={},t){switch(t.type){case"SET_DEFAULT_COMPLEMENTARY_AREA":{const{scope:s,area:r}=t;return e[s]?e:{...e,[s]:r}}case"ENABLE_COMPLEMENTARY_AREA":{const{scope:s,area:r}=t;return{...e,[s]:r}}}return e},activeModal:function(e=null,t){switch(t.type){case"OPEN_MODAL":return t.name;case"CLOSE_MODAL":return null}return e}})},532:(e,t,s)=>{s.r(t),s.d(t,{getActiveComplementaryArea:()=>c,isComplementaryAreaLoading:()=>l,isFeatureActive:()=>p,isItemPinned:()=>u,isModalActive:()=>d});var r=s(7143),n=s(4040),o=s.n(n),a=s(1233),i=s(9561);const c=(0,r.createRegistrySelector)((e=>(t,s)=>{s=(0,i.F)(s);const r=e(a.store).get(s,"isComplementaryAreaVisible");if(void 0!==r)return!1===r?null:t?.complementaryAreas?.[s]})),l=(0,r.createRegistrySelector)((e=>(t,s)=>{s=(0,i.F)(s);const r=e(a.store).get(s,"isComplementaryAreaVisible"),n=t?.complementaryAreas?.[s];return r&&void 0===n})),u=(0,r.createRegistrySelector)((e=>(t,s,r)=>{var n;s=(0,i.F)(s),r=(0,i.M)(s,r);const o=e(a.store).get(s,"pinnedItems");return null===(n=o?.[r])||void 0===n||n})),p=(0,r.createRegistrySelector)((e=>(t,s,r)=>(o()("select( 'core/interface' ).isFeatureActive( scope, featureName )",{since:"6.0",alternative:"select( 'core/preferences' ).get( scope, featureName )"}),!!e(a.store).get(s,r))));function d(e,t){return e.activeModal===t}},3393:(e,t,s)=>{s.d(t,{A:()=>d});var r=s(1609),n=s(6427),o=s(7143),a=s(4309),i=s(3656),c=s(6087),l=s(7723),u=s(7527);const p=VW_CUSTOM_STATUSES.status_terms.map((e=>({label:e.name,value:e.slug})));function d({onUpdateStatus:e,postType:t,status:s}){const[o,i]=(0,c.useState)(!1);if(!(0,u.C)(t,s))return null;const d=VW_CUSTOM_STATUSES.status_terms.find((e=>e.slug===s))?.name;return(0,r.createElement)(a.PluginPostStatusInfo,{className:`vip-workflow-extended-post-status vip-workflow-extended-post-status-${s}`},(0,r.createElement)("h4",null,(0,l.__)("Extended Post Status","vip-workflow")),(0,r.createElement)("div",{className:"vip-workflow-extended-post-status-edit"},!o&&(0,r.createElement)(r.Fragment,null,d,(0,r.createElement)(n.Button,{size:"compact",variant:"link",onClick:()=>i(!0)},(0,l.__)("Edit","vip-workflow"))),o&&(0,r.createElement)(n.SelectControl,{label:"",value:s,options:p,onChange:t=>{e(t),i(!1)}})))}(0,o.subscribe)((function(){(0,o.select)(i.store).getCurrentPostId()&&(0,o.select)(i.store).isCleanNewPost()&&(0,o.dispatch)(i.store).editPost({status:p[0].value})}))},406:(e,t,s)=>{s.d(t,{A:()=>a});var r=s(7143),n=s(6087);const o={};function a(e,t,s){o?.[e]?.[t]||(o?.[e]||(o[e]={}),o[e][t]={actionIntercept:null,callback:s}),(0,n.useEffect)((()=>{o[e][t].callback=s}),[e,t,s]),(0,r.use)((s=>({dispatch:r=>{const n="string"==typeof r?r:r.name,a={...s.dispatch(n)};if(n!==e)return a;if(!o[e][t].actionIntercept){const s=a[t];o[e][t].actionIntercept=(...r)=>{o[e][t].callback(s,r)}}return a[t]=o[e][t].actionIntercept,a}})))}},1987:(e,t,s)=>{s.d(t,{A:()=>a});var r=s(7143),n=s(4764),o=s(406);function a(e,t){const s=()=>(0,r.select)(n.M_).getActiveComplementaryArea("core")===e;(0,o.A)(n.M_.name,"enableComplementaryArea",((r,n)=>{if("core"===n[0]&&n[1]===e){const e=s();t(e,(()=>r(...n)))}else r(...n)})),(0,o.A)(n.M_.name,"disableComplementaryArea",((e,r)=>{const n=s();n?t(n,(()=>e(...r))):e(...r)}))}},7527:(e,t,s)=>{function r(e,t){const s=VW_CUSTOM_STATUSES.supported_post_types.includes(e),r=VW_CUSTOM_STATUSES.status_terms.map((e=>e.slug)).includes(t);return s&&r}s.d(t,{C:()=>r})},1609:e=>{e.exports=window.React},6427:e=>{e.exports=window.wp.components},9491:e=>{e.exports=window.wp.compose},7143:e=>{e.exports=window.wp.data},4040:e=>{e.exports=window.wp.deprecated},4309:e=>{e.exports=window.wp.editPost},3656:e=>{e.exports=window.wp.editor},6087:e=>{e.exports=window.wp.element},7723:e=>{e.exports=window.wp.i18n},2279:e=>{e.exports=window.wp.plugins},1233:e=>{e.exports=window.wp.preferences},4164:(e,t,s)=>{function r(e){var t,s,n="";if("string"==typeof e||"number"==typeof e)n+=e;else if("object"==typeof e)if(Array.isArray(e)){var o=e.length;for(t=0;t<o;t++)e[t]&&(s=r(e[t]))&&(n&&(n+=" "),n+=s)}else for(s in e)e[s]&&(n&&(n+=" "),n+=s);return n}s.d(t,{A:()=>n});const n=function(){for(var e,t,s=0,n="",o=arguments.length;s<o;s++)(e=arguments[s])&&(t=r(e))&&(n&&(n+=" "),n+=t);return n}}},t={};function s(r){var n=t[r];if(void 0!==n)return n.exports;var o=t[r]={exports:{}};return e[r](o,o.exports,s),o.exports}s.n=e=>{var t=e&&e.__esModule?()=>e.default:()=>e;return s.d(t,{a:t}),t},s.d=(e,t)=>{for(var r in t)s.o(t,r)&&!s.o(e,r)&&Object.defineProperty(e,r,{enumerable:!0,get:t[r]})},s.o=(e,t)=>Object.prototype.hasOwnProperty.call(e,t),s.r=e=>{"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})},(()=>{var e=s(1609),t=s(9491),r=s(7143),n=s(4309),o=s(3656),a=s(6087),i=s(7723),c=s(2279),l=s(4164),u=s(3393),p=s(1987);const d="vip-workflow-custom-status",m="vip-workflow-sidebar";(0,c.registerPlugin)(d,{render:(0,t.compose)((0,r.withSelect)((e=>{const{getEditedPostAttribute:t,isSavingPost:s,getCurrentPost:r,getCurrentPostType:n}=e(o.store),a=r(),i="auto-draft"===a?.status;return{postType:n(),status:t("status"),isSavingPost:s(),isUnsavedPost:i}})),(0,r.withDispatch)((e=>({onUpdateStatus(t){e(o.store).editPost({status:t})}}))))((({postType:t,status:s,isUnsavedPost:c,isSavingPost:l,onUpdateStatus:S})=>{const A=(0,a.useMemo)((()=>v(c,t,s)),[c,t,s]);(0,e.useEffect)((()=>{const e=document.querySelector("#editor");A?e.classList.add("disable-native-save-button"):e.classList.remove("disable-native-save-button")}),[A]);const _=(0,a.useMemo)((()=>g(s)),[s]);let w;(0,p.A)(`${d}/${m}`,((e,t)=>{_&&(S(_.slug),(0,r.dispatch)(o.store).savePost())})),w=c?(0,i.__)("Save","vip-workflow"):_?(0,i.sprintf)((0,i.__)("Move to %s","vip-workflow"),_.name):(0,i.__)("Publish 123 123","vip-workflow");const y=(0,e.createElement)(f,{buttonText:w,isSavingPost:l});return(0,e.createElement)(e.Fragment,null,(0,e.createElement)(u.A,{postType:t,status:s,onUpdateStatus:S}),A&&(0,e.createElement)(n.PluginSidebar,{name:m,title:w,icon:y},null))}))});const f=({buttonText:t,isSavingPost:s})=>{const r=(0,l.A)("vip-workflow-save-button",{"is-busy":s});return(0,e.createElement)("div",{className:r},t)},v=(e,t,s)=>{if(e)return!1;const r=VW_CUSTOM_STATUSES.supported_post_types.includes(t),n=VW_CUSTOM_STATUSES.status_terms.slice(0,-1).map((e=>e.slug)).includes(s);return r&&n},g=e=>{const t=VW_CUSTOM_STATUSES.status_terms.findIndex((t=>t.slug===e));return-1!==t&&t!==VW_CUSTOM_STATUSES.status_terms.length-1&&VW_CUSTOM_STATUSES.status_terms[t+1]}})()})();
//# sourceMappingURL=custom-status-block.js.map