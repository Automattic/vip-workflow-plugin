(()=>{"use strict";var e={1609:e=>{e.exports=window.React},6427:e=>{e.exports=window.wp.components},4309:e=>{e.exports=window.wp.editPost},2279:e=>{e.exports=window.wp.plugins}},t={};function r(o){var a=t[o];if(void 0!==a)return a.exports;var n=t[o]={exports:{}};return e[o](n,n.exports,r),n.exports}r.n=e=>{var t=e&&e.__esModule?()=>e.default:()=>e;return r.d(t,{a:t}),t},r.d=(e,t)=>{for(var o in t)r.o(t,o)&&!r.o(e,o)&&Object.defineProperty(e,o,{enumerable:!0,get:t[o]})},r.o=(e,t)=>Object.prototype.hasOwnProperty.call(e,t),(()=>{var e=r(1609),t=r(6427),o=r(4309),a=r(2279);const n=window.VipWorkflowEditorialMetadatas.map((e=>({key:e.meta_key,label:e.name,type:e.type,term_id:e.term_id,description:e.description})));(0,a.registerPlugin)("vip-workflow-editorial-metadata",{render:function(){return(0,e.createElement)(o.PluginDocumentSettingPanel,{name:"editorialMetadataPanel",title:"Editorial Metadata"},(0,e.createElement)(t.PanelRow,null,n.map((r=>(0,e.createElement)(t.TextControl,{key:r.key,label:r.label,value:r.type,className:r.key})))))},icon:"vip-workflow"})})()})();
//# sourceMappingURL=editorial-metadata-block.js.map