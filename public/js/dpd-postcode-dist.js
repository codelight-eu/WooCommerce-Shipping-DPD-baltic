"use strict";var dpd_gmap_api_key=dpd.gmap_api_key,dpd_gmap_base_url="https://maps.googleapis.com/maps/api/geocode/json";!function(o){function p(e){var a,n;try{for(var s=0;s<e[0].address_components.length;s++)if(n=e[0].address_components[s],-1<e[0].address_components[s].types.indexOf("postal_code")){a=e[0].address_components[s].short_name;break}}catch(e){}return a}function t(e,n){if(dpd_gmap_api_key){var a=jQuery.param({address:e,type:"postal_code",key:dpd_gmap_api_key}),s=dpd_gmap_base_url+"?"+a;n.prop("readonly",!0),o.getJSON(s,function(e){if("OK"==e.status){var a=p(e.results);a&&(n.val(a),n.trigger("change"))}else console.log("Google Maps Geocoder Error",e),n.val(""),n.trigger("change");n.prop("readonly",!1)})}else console.log("Postal code geocoder is missing Google Maps API key")}function e(e){var a=o('input[name="'+e+'_city"]'),n=o('input[name="'+e+'_address_1"]'),s=o('input[name="'+e+'_address_2"]'),p=o('input[name="'+e+'_postcode"]'),i="";i+=n.val()?n.val()+" ":"",i+=s.val()?s.val()+" ":"",i+=a.val()?a.val()+" ":"",n.val()&&a.val()&&p.get(0)&&t(i,p)}o(document).ready(function(){o(document).ajaxStop(function(){o('input[name="billing_city"], input[name="billing_address_1"], input[name="billing_address_2"]').off("change",e("billing")).change(e("billing")),o('input[name="shipping_city"], input[name="shipping_address_1"], input[name="shipping_address_2"]').off("change",e("shipping")).change(e("shipping"))})})}(jQuery);