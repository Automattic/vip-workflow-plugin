(()=>{"use strict";var t={609:t=>{t.exports=window.React},427:t=>{t.exports=window.wp.components},491:t=>{t.exports=window.wp.compose},143:t=>{t.exports=window.wp.data},309:t=>{t.exports=window.wp.editPost},723:t=>{t.exports=window.wp.i18n},279:t=>{t.exports=window.wp.plugins}},e={};function r(o){var n=e[o];if(void 0!==n)return n.exports;var s=e[o]={exports:{}};return t[o](s,s.exports,r),s.exports}r.n=t=>{var e=t&&t.__esModule?()=>t.default:()=>t;return r.d(e,{a:e}),e},r.d=(t,e)=>{for(var o in e)r.o(e,o)&&!r.o(t,o)&&Object.defineProperty(t,o,{enumerable:!0,get:e[o]})},r.o=(t,e)=>Object.prototype.hasOwnProperty.call(t,e),(()=>{var t=r(609),e=r.n(t),o=r(427),n=r(491),s=r(143),i=r(309),a=r(723),u=r(279);function l(t,e){var r="undefined"!=typeof Symbol&&t[Symbol.iterator]||t["@@iterator"];if(!r){if(Array.isArray(t)||(r=function(t,e){if(t){if("string"==typeof t)return c(t,e);var r=Object.prototype.toString.call(t).slice(8,-1);return"Object"===r&&t.constructor&&(r=t.constructor.name),"Map"===r||"Set"===r?Array.from(t):"Arguments"===r||/^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(r)?c(t,e):void 0}}(t))||e&&t&&"number"==typeof t.length){r&&(t=r);var o=0,n=function(){};return{s:n,n:function(){return o>=t.length?{done:!0}:{done:!1,value:t[o++]}},e:function(t){throw t},f:n}}throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.")}var s,i=!0,a=!1;return{s:function(){r=r.call(t)},n:function(){var t=r.next();return i=t.done,t},e:function(t){a=!0,s=t},f:function(){try{i||null==r.return||r.return()}finally{if(a)throw s}}}}function c(t,e){(null==e||e>t.length)&&(e=t.length);for(var r=0,o=new Array(e);r<e;r++)o[r]=t[r];return o}var d=window.VipWorkflowCustomStatuses.map((function(t){return{label:t.name,value:t.slug}})),f=null;function p(t){t&&t.children<1&&(t.innerText===(0,a.__)("Save Draft")||t.innerText===(0,a.__)("Save as Pending"))&&(t.innerText=(0,a.__)("Save"))}(0,s.subscribe)((function(){(0,s.select)("core/editor").getCurrentPostId()&&((0,s.select)("core/editor").isCleanNewPost()&&(0,s.dispatch)("core/editor").editPost({status:vw_default_custom_status}),p(document.querySelector(".editor-post-save-draft")),null===f&&window.MutationObserver&&(0,s.select)("core/editor").isSavingPost()&&(f=function(t){if(!t)return null;var e=new MutationObserver((function(t){var e,r=l(t);try{for(r.s();!(e=r.n()).done;){var o,n=l(e.value.addedNodes);try{for(n.s();!(o=n.n()).done;)p(o.value)}catch(t){n.e(t)}finally{n.f()}}}catch(t){r.e(t)}finally{r.f()}}));return e.observe(t,{childList:!0}),e}(document.querySelector(".edit-post-header__settings"))))}));var w=(0,n.compose)((0,s.withSelect)((function(t){return{status:t("core/editor").getEditedPostAttribute("status")}})),(0,s.withDispatch)((function(t){return{onUpdate:function(e){t("core/editor").editPost({status:e})}}})))((function(t){var r=t.onUpdate,n=t.status;return e().createElement(i.PluginPostStatusInfo,{className:"vip-workflow-extended-post-status vip-workflow-extended-post-status-".concat(n)},e().createElement("h4",null,"publish"!==n?(0,a.__)("Extended Post Status","vip-workflow"):(0,a.__)("Extended Post Status Disabled.","vip-workflow")),"publish"!==n?e().createElement(o.SelectControl,{label:"",value:n,options:d,onChange:r}):null,e().createElement("small",{className:"vip-workflow-extended-post-status-note"},"publish"!==n?(0,a.__)("Note: this will override all status settings above.","vip-workflow"):(0,a.__)("To select a custom status, please unpublish the content first.","vip-workflow")))}));(0,u.registerPlugin)("vip-workflow-custom-status",{icon:"vip-workflow",render:w})})()})();
//# sourceMappingURL=custom-status-block.js.map