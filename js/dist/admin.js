module.exports=function(e){var t={};function a(n){if(t[n])return t[n].exports;var r=t[n]={i:n,l:!1,exports:{}};return e[n].call(r.exports,r,r.exports,a),r.l=!0,r.exports}return a.m=e,a.c=t,a.d=function(e,t,n){a.o(e,t)||Object.defineProperty(e,t,{enumerable:!0,get:n})},a.r=function(e){"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})},a.t=function(e,t){if(1&t&&(e=a(e)),8&t)return e;if(4&t&&"object"==typeof e&&e&&e.__esModule)return e;var n=Object.create(null);if(a.r(n),Object.defineProperty(n,"default",{enumerable:!0,value:e}),2&t&&"string"!=typeof e)for(var r in e)a.d(n,r,function(t){return e[t]}.bind(null,r));return n},a.n=function(e){var t=e&&e.__esModule?function(){return e.default}:function(){return e};return a.d(t,"a",t),t},a.o=function(e,t){return Object.prototype.hasOwnProperty.call(e,t)},a.p="",a(a.s=10)}([function(e,t){e.exports=flarum.core.compat["admin/app"]},,function(e,t){e.exports=flarum.core.compat["common/extend"]},,,function(e,t){e.exports=flarum.core.compat["common/components/Link"]},function(e,t){e.exports=flarum.core.compat["admin/components/StatusWidget"]},function(e,t){e.exports=flarum.core.compat["common/components/Button"]},,,function(e,t,a){"use strict";a.r(t);var n=a(0),r=a.n(n),l=a(5),c=a.n(l),o=a(2),s=a(6),i=a.n(s),u=a(7),p=a.n(u);function d(){r.a.request({url:r.a.forum.attribute("apiUrl")+"/lscache-purge",method:"DELETE"}).then((function(){r.a.alerts.show({type:"success"},r.a.translator.trans("acpl-lscache.admin.purge_all_success"))}))}r.a.initializers.add("acpl-lscache",(function(){r.a.extensionData.for("acpl-lscache").registerSetting({setting:"acpl-lscache.public_cache_ttl",label:r.a.translator.trans("acpl-lscache.admin.public_cache_ttl_label"),help:r.a.translator.trans("acpl-lscache.admin.public_cache_ttl_help"),type:"number",min:30}).registerSetting({setting:"acpl-lscache.serve_stale",label:r.a.translator.trans("acpl-lscache.admin.serve_stale_label"),help:r.a.translator.trans("acpl-lscache.admin.serve_stale_help"),type:"boolean"}).registerSetting((function(){return m("div",{className:"Form-group"},m("label",{htmlFor:"purge_link_list"},r.a.translator.trans("acpl-lscache.admin.purge_on_discussion_update_label")),m("div",{className:"helpText"},r.a.translator.trans("acpl-lscache.admin.purge_on_discussion_update_help",{a:m(c.a,{href:"https://docs.litespeedtech.com/lscache/devguide/controls/#cache-tag",external:!0,target:"_blank"})})),m("textarea",{id:"purge_link_list",className:"FormControl",rows:4,bidi:this.setting("acpl-lscache.purge_on_discussion_update")}))})).registerSetting((function(){return m("div",{className:"Form-group"},m("label",{htmlFor:"purge_link_list"},r.a.translator.trans("acpl-lscache.admin.cache_exclude_label")),m("div",{className:"helpText"},r.a.translator.trans("acpl-lscache.admin.cache_exclude_help")),m("textarea",{id:"purge_link_list",className:"FormControl",rows:4,bidi:this.setting("acpl-lscache.cache_exclude")}))})),Object(o.extend)(i.a.prototype,"items",(function(e){e.get("tools").children.push(m(p.a,{onclick:d},r.a.translator.trans("acpl-lscache.admin.purge_all")))}))}))}]);
//# sourceMappingURL=admin.js.map