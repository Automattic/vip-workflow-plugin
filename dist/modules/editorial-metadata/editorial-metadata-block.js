(()=>{"use strict";var e={1609:e=>{e.exports=window.React},6427:e=>{e.exports=window.wp.components},9491:e=>{e.exports=window.wp.compose},7143:e=>{e.exports=window.wp.data},4309:e=>{e.exports=window.wp.editPost},2279:e=>{e.exports=window.wp.plugins}},t={};function o(r){var a=t[r];if(void 0!==a)return a.exports;var i=t[r]={exports:{}};return e[r](i,i.exports,o),i.exports}o.n=e=>{var t=e&&e.__esModule?()=>e.default:()=>e;return o.d(t,{a:t}),t},o.d=(e,t)=>{for(var r in t)o.o(t,r)&&!o.o(e,r)&&Object.defineProperty(e,r,{enumerable:!0,get:t[r]})},o.o=(e,t)=>Object.prototype.hasOwnProperty.call(e,t),(()=>{var e=o(1609),t=o(6427),r=o(9491),a=o(7143),i=o(4309),n=o(2279);const l=window.VipWorkflowEditorialMetadatas.map((e=>({key:e.meta_key,label:e.name,type:e.type,term_id:e.term_id,description:e.description}))),d=(0,r.compose)((0,a.withSelect)((e=>({metaFields:e("core/editor").getEditedPostAttribute("meta")}))),(0,a.withDispatch)((e=>({setMetaFields(t){e("core/editor").editPost({meta:t})}}))))((({metaFields:o,setMetaFields:r})=>(0,e.createElement)(i.PluginDocumentSettingPanel,{name:"editorialMetadataPanel",title:"Editorial Metadata"},(0,e.createElement)(t.PanelBody,null,l.filter((e=>"text"===e.type)).map((a=>(console.log(o?.[a.key]),(0,e.createElement)(t.TextControl,{key:a.key,label:a.label,className:a.key,onChange:e=>r({...o,[a.key]:e})}))))))));(0,n.registerPlugin)("vip-workflow-editorial-metadata",{render:d,icon:"vip-workflow"})})()})();
//# sourceMappingURL=editorial-metadata-block.js.map