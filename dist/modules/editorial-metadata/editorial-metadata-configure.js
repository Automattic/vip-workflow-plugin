(()=>{"use strict";var e,t,a,r,l,n={1366:(e,t,a)=>{a.d(t,{A:()=>p});var r=a(1609),l=a(1455),n=a.n(l),o=a(6427),i=a(6087),s=a(7723),d=a(6761),c=a(6870),m=a(7112),u=a(9253);function p({supportedMetadataTypes:e,editorialMetadataTerms:t}){const[a,l]=(0,i.useState)(null),[p,_]=(0,i.useState)(null),[w]=(0,i.useState)(e),[f,E]=(0,i.useState)(t),[v,y]=(0,i.useState)(null),[k,T]=(0,i.useState)(!1),[A,C]=(0,i.useState)(!1),x=(e,t)=>{_(null),l(e),E(v&&!k?f.map((e=>e.term_id===t.term_id?t:e)):k?f.filter((e=>e.term_id!==t.term_id)):[...f,t]),C(!1),T(!1)},h=(0,r.createElement)(m.A,{confirmationMessage:"",dataType:"metadata field",name:v?.name,onCancel:()=>T(!1),onConfirmDelete:async()=>{try{await n()({url:VW_EDITORIAL_METADATA_CONFIGURE.url_edit_editorial_metadata+v.term_id,method:"DELETE"}),x((0,s.sprintf)((0,s.__)('Editorial Metadata "%s" deleted successfully.',"vip-workflow"),v.name),v)}catch(e){(e=>{l(null),_(e),T(!1),y(null)})(e.message)}}}),g=(0,r.createElement)(d.A,{availableMetadataTypes:w.map((e=>({value:e,label:e}))),metadata:v,onCancel:()=>C(!1),onSuccess:x});return(0,r.createElement)(r.Fragment,null,(0,r.createElement)(u.A,{success:a}),p&&(0,r.createElement)(c.A,{errorMessage:p,setError:_}),(0,r.createElement)(o.Flex,{direction:["column"],justify:"start",align:"start"},(0,r.createElement)(o.FlexItem,null,(0,r.createElement)(o.Button,{variant:"secondary",onClick:()=>{y(null),C(!0)}},(0,s.__)("Add New Metadata","vip-workflow"))),(0,r.createElement)(o.Flex,{className:"emetadata-items",direction:["column","row"],justify:"start"},f.map((e=>(0,r.createElement)(o.FlexItem,{className:"emetadata-item",key:e.term_id},(0,r.createElement)(o.Card,null,(0,r.createElement)(o.CardHeader,null,(0,r.createElement)(o.Flex,{direction:["column"],justify:"start",align:"start"},(0,r.createElement)(o.__experimentalHeading,{level:4},e.name),(0,r.createElement)(o.__experimentalText,null,(0,r.createElement)("i",null,e.description))),(0,r.createElement)(o.Flex,{direction:["column","row"],justify:"end",align:"end"},(0,r.createElement)("div",{className:"crud-emetadata"},(0,r.createElement)(o.Button,{size:"compact",className:"delete-emetadata",variant:"secondary",onClick:()=>{y(e),T(!0)},style:{color:"#b32d2e",boxShadow:"inset 0 0 0 1px #b32d2e"}},(0,s.__)("Delete","vip-workflow")),(0,r.createElement)(o.Button,{size:"compact",variant:"primary",onClick:()=>{y(e),C(!0)}},(0,s.__)("Edit","vip-workflow"))))))))))),k&&h,A&&g)}},6761:(e,t,a)=>{a.d(t,{A:()=>c});var r=a(1609),l=a(1455),n=a.n(l),o=a(6427),i=a(6087),s=a(7723),d=a(6870);function c({availableMetadataTypes:e,metadata:t,onCancel:a,onSuccess:l}){const[c,m]=(0,i.useState)(null),[u,p]=(0,i.useState)(t?.name||""),[_,w]=(0,i.useState)(t?.description||""),[f,E]=(0,i.useState)(t?.type||e[0].value),[v,y]=(0,i.useState)(!1);let k;return k=t?(0,s.sprintf)((0,s.__)('Edit "%s"',"vip-workflow"),t.name):(0,s.__)("Add New Editorial Metadata","vip-workflow"),(0,r.createElement)(o.Modal,{title:k,size:"medium",onRequestClose:a,closeButtonLabel:(0,s.__)("Cancel","vip-workflow")},c&&(0,r.createElement)(d.A,{errorMessage:c,setError:m}),(0,r.createElement)(o.TextControl,{help:(0,s.__)("The name is used to identify the editorial metadata field.","vip-workflow"),label:(0,s.__)("Name","vip-workflow"),onChange:p,value:u}),(0,r.createElement)(o.TextareaControl,{help:(0,s.__)("The description is primarily for your team to provide context on how the editorial metadata field should be used.","vip-workflow"),label:(0,s.__)("Description","vip-workflow"),onChange:w,value:_}),(0,r.createElement)(o.SelectControl,{help:(0,s.__)("This is to identify the type for the editorial metadata field.","vip-workflow"),label:(0,s.__)("Type","vip-workflow"),value:f,options:e,onChange:E}),(0,r.createElement)(o.Button,{variant:"primary",onClick:async()=>{const e={name:u,description:_,type:f};try{y(!0);const a=await n()({url:VW_EDITORIAL_METADATA_CONFIGURE.url_edit_editorial_metadata+(t?t.term_id:""),method:t?"PUT":"POST",data:e});l(t?(0,s.sprintf)((0,s.__)('Editorial Metadata "%s" updated successfully.',"vip-workflow"),u):(0,s.sprintf)((0,s.__)('Editorial Metadata "%s" added successfully.',"vip-workflow"),u),a)}catch(e){m(e.message)}y(!1)},disabled:v},t?(0,s.__)("Save Changes","vip-workflow"):(0,s.__)("Add Metadata","vip-workflow")))}},6870:(e,t,a)=>{a.d(t,{A:()=>n});var r=a(1609),l=a(6427);function n({errorMessage:e,setError:t}){return(0,r.createElement)("div",{style:{marginBottom:"1rem"}},(0,r.createElement)(l.Notice,{status:"error",isDismissible:!0,onRemove:()=>t(null)},(0,r.createElement)("p",null,e)))}},7112:(e,t,a)=>{a.d(t,{A:()=>o});var r=a(1609),l=a(6427),n=a(7723);function o({confirmationMessage:e,dataType:t,name:a,onCancel:o,onConfirmDelete:i}){return(0,r.createElement)(l.Modal,{title:(0,n.sprintf)((0,n.__)("Delete %s?","vip-workflow"),a),size:"medium",onRequestClose:o,closeButtonLabel:(0,n.__)("Cancel","vip-workflow")},(0,r.createElement)("p",null,(0,n.sprintf)((0,n.__)('Are you sure you want to delete "%1$s"? %2$s',"vip-workflow"),a,e)),(0,r.createElement)("strong",{style:{display:"block",marginTop:"1rem"}},(0,n.__)("This action can not be undone.","vip-workflow")),(0,r.createElement)(l.Flex,{direction:"row",justify:"flex-end"},(0,r.createElement)(l.Button,{variant:"tertiary",onClick:o},(0,n.__)("Cancel","vip-workflow")),(0,r.createElement)(l.Button,{variant:"primary",onClick:i,style:{background:"#b32d2e"}},(0,n.sprintf)((0,n.__)("Delete this %1$s","vip-workflow"),t))))}},9253:(e,t,a)=>{a.d(t,{A:()=>l});var r=a(6087);function l({success:e}){const t=(0,r.useRef)(null);return(0,r.useEffect)((()=>{const e=document.querySelector(".vip-workflow-admin h2"),a=document.createElement("span");a.classList.add("vip-workflow-updated-message","vip-workflow-message"),a.style.opacity="0",e.append(a),t.current=a}),[]),(0,r.useEffect)((()=>{e?(t.current.textContent=e,t.current.style.opacity="1"):t.current.style.opacity="0"}),[e]),null}},1609:e=>{e.exports=window.React},1455:e=>{e.exports=window.wp.apiFetch},6427:e=>{e.exports=window.wp.components},8490:e=>{e.exports=window.wp.domReady},6087:e=>{e.exports=window.wp.element},7723:e=>{e.exports=window.wp.i18n}},o={};function i(e){var t=o[e];if(void 0!==t)return t.exports;var a=o[e]={exports:{}};return n[e](a,a.exports,i),a.exports}i.n=e=>{var t=e&&e.__esModule?()=>e.default:()=>e;return i.d(t,{a:t}),t},i.d=(e,t)=>{for(var a in t)i.o(t,a)&&!i.o(e,a)&&Object.defineProperty(e,a,{enumerable:!0,get:t[a]})},i.o=(e,t)=>Object.prototype.hasOwnProperty.call(e,t),e=i(1609),t=i(8490),a=i.n(t),r=i(6087),l=i(1366),a()((()=>{const t=document.getElementById("editorial-metadata-manager");t&&(0,r.createRoot)(t).render((0,e.createElement)(l.A,{supportedMetadataTypes:VW_EDITORIAL_METADATA_CONFIGURE.supported_metadata_types,editorialMetadataTerms:VW_EDITORIAL_METADATA_CONFIGURE.editorial_metadata_terms}))}))})();
//# sourceMappingURL=editorial-metadata-configure.js.map