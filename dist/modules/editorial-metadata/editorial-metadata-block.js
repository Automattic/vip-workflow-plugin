(()=>{"use strict";var e={8053:(e,t,a)=>{a.d(t,{A:()=>o});var r=a(5573),n=a(4848);const o=(0,n.jsx)(r.SVG,{xmlns:"http://www.w3.org/2000/svg",viewBox:"0 0 24 24",children:(0,n.jsx)(r.Path,{d:"M12 13.06l3.712 3.713 1.061-1.06L13.061 12l3.712-3.712-1.06-1.06L12 10.938 8.288 7.227l-1.061 1.06L10.939 12l-3.712 3.712 1.06 1.061L12 13.061z"})})},1020:(e,t,a)=>{var r=a(1609),n=Symbol.for("react.element"),o=(Symbol.for("react.fragment"),Object.prototype.hasOwnProperty),l=r.__SECRET_INTERNALS_DO_NOT_USE_OR_YOU_WILL_BE_FIRED.ReactCurrentOwner,i={key:!0,ref:!0,__self:!0,__source:!0};t.jsx=function(e,t,a){var r,s={},c=null,d=null;for(r in void 0!==a&&(c=""+a),void 0!==t.key&&(c=""+t.key),void 0!==t.ref&&(d=t.ref),t)o.call(t,r)&&!i.hasOwnProperty(r)&&(s[r]=t[r]);if(e&&e.defaultProps)for(r in t=e.defaultProps)void 0===s[r]&&(s[r]=t[r]);return{$$typeof:n,type:e,key:c,ref:d,props:s,_owner:l.current}}},4848:(e,t,a)=>{e.exports=a(1020)},1609:e=>{e.exports=window.React},6427:e=>{e.exports=window.wp.components},9491:e=>{e.exports=window.wp.compose},7143:e=>{e.exports=window.wp.data},8443:e=>{e.exports=window.wp.date},4309:e=>{e.exports=window.wp.editPost},6087:e=>{e.exports=window.wp.element},7723:e=>{e.exports=window.wp.i18n},2279:e=>{e.exports=window.wp.plugins},5573:e=>{e.exports=window.wp.primitives}},t={};function a(r){var n=t[r];if(void 0!==n)return n.exports;var o=t[r]={exports:{}};return e[r](o,o.exports,a),o.exports}a.n=e=>{var t=e&&e.__esModule?()=>e.default:()=>e;return a.d(t,{a:t}),t},a.d=(e,t)=>{for(var r in t)a.o(t,r)&&!a.o(e,r)&&Object.defineProperty(e,r,{enumerable:!0,get:t[r]})},a.o=(e,t)=>Object.prototype.hasOwnProperty.call(e,t),(()=>{var e=a(1609),t=a(6427),r=a(9491),n=a(7143),o=a(8443),l=a(4309),i=a(6087),s=a(7723),c=a(8053),d=a(2279);const m=window.VW_EDITORIAL_METADATA.editorial_metadata_terms.map((e=>({key:e.meta_key,label:e.name,type:e.type,term_id:e.term_id,description:e.description}))),p=0!==m.length,_=({editorialMetadata:a,metaFields:r,setMetaFields:n})=>(0,e.createElement)(t.__experimentalHStack,{__nextHasNoMarginBottom:!0},(0,e.createElement)("label",{title:a.description},a.label),(0,e.createElement)(t.ToggleControl,{__nextHasNoMarginBottom:!0,checked:r?.[a.key],onChange:e=>n({...r,[a.key]:e})})),u=({editorialMetadata:a,metaFields:r,setMetaFields:n})=>{const[o,l]=(0,i.useState)(null),d=(0,i.useMemo)((()=>({anchor:o,"aria-label":(0,s.__)("Select date"),placement:"left-start",offset:36,shift:!0})),[o]),m=r?.[a.key]||(0,s.__)("None");return(0,e.createElement)(t.Dropdown,{ref:l,popoverProps:d,focusOnMount:!0,renderToggle:({onToggle:r,isOpen:n})=>(0,e.createElement)(t.__experimentalHStack,{__nextHasNoMarginBottom:!0},(0,e.createElement)("label",{title:a.description},a.label),(0,e.createElement)(t.Button,{size:"compact",variant:"tertiary",onClick:r,"aria-label":a.label,"aria-expanded":n},m)),renderContent:({onClose:o})=>(0,e.createElement)(t.__experimentalVStack,{__nextHasNoMarginBottom:!0},(0,e.createElement)(t.__experimentalHStack,{__nextHasNoMarginBottom:!0},(0,e.createElement)(t.__experimentalHeading,{level:2,size:13},a.label),(0,e.createElement)(t.Button,{label:(0,s.__)("Close"),icon:c.A,onClick:o})),(0,e.createElement)(t.BaseControl,{__nextHasNoMarginBottom:!0,label:a.label}),(0,e.createElement)(t.TextControl,{__nextHasNoMarginBottom:!0,value:r?.[a.key],className:a.key,onChange:e=>n({...r,[a.key]:e})}),(0,e.createElement)(t.BaseControl,{help:a.description}),(0,e.createElement)(t.Flex,{direction:["row"],justify:"end",align:"end"},(0,e.createElement)(t.Button,{label:(0,s.__)("Clear"),variant:"tertiary",onClick:()=>{n({...r,[a.key]:""}),o()}},(0,s.__)("Clear"))))})},w=({editorialMetadata:a,metaFields:r,setMetaFields:n})=>{const[l,d]=(0,i.useState)(null),m=(0,i.useMemo)((()=>({anchor:l,"aria-label":(0,s.__)("Select date"),placement:"left-start",offset:36,shift:!0})),[l]);let p=r?.[a.key];const _=(0,o.getSettings)(),u=/a(?!\\)/i.test(_.formats.time.toLowerCase().replace(/\\\\/g,"").split("").reverse().join(""));return p=p?f({dateAttribute:p}):(0,s.__)("None"),(0,e.createElement)(t.Dropdown,{ref:d,popoverProps:m,focusOnMount:!0,renderToggle:({onToggle:r,isOpen:n})=>(0,e.createElement)(t.__experimentalHStack,{__nextHasNoMarginBottom:!0},(0,e.createElement)("label",{title:a.description},a.label),(0,e.createElement)(t.Button,{style:{whiteSpace:"normal"},size:"compact",variant:"tertiary",onClick:r,"aria-label":a.label,"aria-expanded":n},p)),renderContent:({onClose:o})=>{var l;return(0,e.createElement)(t.Flex,{direction:["column"],justify:"start",align:"centre"},(0,e.createElement)(t.Flex,{direction:["row"],justify:"start",align:"end"},(0,e.createElement)(t.__experimentalHeading,{level:2,size:13},a.label),(0,e.createElement)(t.Flex,{direction:["row"],justify:"end",align:"end"},(0,e.createElement)(t.Button,{label:(0,s.__)("Now"),variant:"tertiary",onClick:()=>{n({...r,[a.key]:new Date})}},(0,s.__)("Now")),(0,e.createElement)(t.Button,{label:(0,s.__)("Close"),icon:c.A,onClick:o}))),(0,e.createElement)(t.DateTimePicker,{currentDate:null!==(l=r?.[a.key])&&void 0!==l?l:void 0,value:r?.[a.key],is12Hour:u,onChange:e=>n({...r,[a.key]:e}),onClose:o}),(0,e.createElement)(t.Flex,{direction:["row"],justify:"end",align:"end"},(0,e.createElement)(t.Button,{label:(0,s.__)("Clear"),variant:"tertiary",onClick:()=>{n({...r,[a.key]:""}),o()}},(0,s.__)("Clear"))))}})},f=({dateAttribute:e})=>{const t=(0,o.getDate)(e),a=(()=>{const{timezone:e}=(0,o.getSettings)();return e.abbr&&isNaN(Number(e.abbr))?e.abbr:`UTC${e.offset<0?"":"+"}${e.offsetFormatted}`})(),r=(0,o.dateI18n)(
// translators: If using a space between 'g:i' and 'a', use a non-breaking space.
// translators: If using a space between 'g:i' and 'a', use a non-breaking space.
(0,s._x)("F j, Y g:i a","full date format"),t);return(0,s.isRTL)()?`${a} ${r}`:`${r} ${a}`},y=(0,r.compose)((0,n.withSelect)((e=>({metaFields:e("core/editor").getEditedPostAttribute("meta")}))),(0,n.withDispatch)((e=>({setMetaFields(t){e("core/editor").editPost({meta:t})}}))))((({metaFields:a,setMetaFields:r})=>(0,e.createElement)(l.PluginPostStatusInfo,{className:"vip-workflow-editorial-metadata"},(0,e.createElement)("div",{className:"vip-workflow-editorial-metadata-row"},(0,e.createElement)(t.__experimentalDivider,null),(0,e.createElement)(t.__experimentalVStack,{spacing:4},p&&m.map((t=>((t,a,r)=>{switch(t.type){case"checkbox":return(0,e.createElement)(_,{key:t.key,editorialMetadata:t,metaFields:a,setMetaFields:r});case"text":return(0,e.createElement)(u,{key:t.key,editorialMetadata:t,metaFields:a,setMetaFields:r});case"date":return(0,e.createElement)(w,{key:t.key,editorialMetadata:t,metaFields:a,setMetaFields:r});default:return null}})(t,a,r))))))));(0,d.registerPlugin)("vip-workflow-editorial-metadata",{render:y,icon:"vip-workflow"})})()})();
//# sourceMappingURL=editorial-metadata-block.js.map