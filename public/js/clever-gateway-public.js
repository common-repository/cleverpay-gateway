var cleverRedirectFunc = null;
var cleverClosePopupFunc = null;

const DISABLED= "disabled";
const HIDDEN = "hidden";

(function($) {
    "use strict";

    jQuery(document).ready(function($) {

        // $(".cl_hint").click(function(event) {
        //     MicroModal.show("clever-payment-popup");
        // });

        // setTimeout(() => {
        //     setInterval(() => {
        //         if (
        //             !$(".wc_payment_method.payment_method_clever_gateway .cl_hint").length
        //         )
        //             $(".cl_hint").prependTo(
        //                 ".wc_payment_method.payment_method_clever_gateway"
        //             );
        //     }, 1000);
        // }, 2000);

        function PopupCenter(url, title, w, h) {
            const dualScreenLeft =
                window.screenLeft !== undefined ? window.screenLeft : window.screenX;
            const dualScreenTop =
                window.screenTop !== undefined ? window.screenTop : window.screenY;

            const width = window.innerWidth ?
                window.innerWidth :
                document.documentElement.clientWidth ?
                document.documentElement.clientWidth :
                screen.width;
            const height = window.innerHeight ?
                window.innerHeight :
                document.documentElement.clientHeight ?
                document.documentElement.clientHeight :
                screen.height;

            const systemZoom = width / window.screen.availWidth;
            const left = (width - w) / 2 / systemZoom + dualScreenLeft;
            const top = (height - h) / 2 / systemZoom + dualScreenTop;
            const newWindow = window.open(
                url,
                title,
                `
          scrollbars=yes,
          width=${w / systemZoom}, 
          height=${h / systemZoom}, 
          top=${top}, 
          left=${left}
          `
            );

            if (
                !newWindow ||
                newWindow.closed ||
                typeof newWindow.closed == "undefined"
            ) {
                alert(
                    "To continue using Hello Clever payment gateway, please enable popups in your browser."
                );
            } else if (window.focus) newWindow.focus();

            return newWindow;
        }

        function toggleExpressCheckoutPopup(flag = true) {
            if (flag) {
                MicroModal.show("clever-express-checkout-popup", {
                    disableScroll: true,
                });
            } else {
                MicroModal.close("clever-express-checkout-popup");
                MicroModal.close("clever-offer-popup");
                $(".clever-loading-m").removeClass("in, cl-express-checkout");
                $("#checkout-express-popup-content iframe").attr("src", "");
                $("#clever-offer-iframe").attr("src", "");
               
                setTimeout(() => {$(".clever-loading-m").removeClass("in")}, 200)
            }
        }



        $(".cl_close").click(function() {
            toggleExpressCheckoutPopup(false);
        });

        const checkout = {
            cashback_timer: null,
            timer: null,
            is_standard: false,
            cashback_rate: "",
            cashback_type: "",
            checkout_express_ready: false,
            child_window: null,
            interval_check_child: null,
            out_of_stock: false,
            configs: $("#clever-configs").length ?
                JSON.parse($("#clever-configs").val()) :
                {
                    ajax_url: ""
                },
            urlParams: new URL(window.location.href),
            text: {
                variation_empty: "Hey, looks like you forget to select some attributes for the product",
            },
            LOADING(flag = true, className = "in") {
                const loadingEl = $(".clever-loading-m");
                if (flag) loadingEl.addClass(className);
                else loadingEl.removeClass(className);
            },
            create(product_id, quantity, variation_id = "", attributes = "", offerMode = false) {
                const this_ = this;
                this.LOADING(true);

                let token = localStorage.getItem("ct_token") || "";

                $.ajax({
                        url: this.configs.ajax_url,
                        type: "POST",
                        dataType: "json",
                        data: {
                            action: "clever_create_checkout_express",
                            product_id,
                            variation_id,
                            quantity,
                            attributes,
                            token,
                        },
                    })
                    .done(function(res) {
                        if(offerMode){
                            document.getElementById('clever-offer-iframe').contentWindow.postMessage({event_id: 'express_checkout', data: res}, '*');
                        }
                        else if (res.hasOwnProperty("redirect_url")) {
                            if (
                                /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(
                                    navigator.userAgent
                                )
                            ) {
                                window.location.href = res.redirect_url;
                            } else {

                                    $("#checkout-express-popup-content iframe").attr(
                                        "src",
                                        res.redirect_url
                                    );
                                    setTimeout(() => {
                                        toggleExpressCheckoutPopup();
                                        $(".clever-loading-m")
                                            .addClass("cl-express-checkout")
                                            .removeClass("in");
                                    }, 400);
                                

                            }
                        }
                    })
                    .always(function() {});
            },
            checkWindow() {
                if (this.child_window && this.child_window.closed) {
                    window.clearInterval(this.interval_check_child);
                    this.LOADING(false, "cl-express-checkout");
                }
                if (!this.interval_check_child)
                    this.interval_check_child = window.setInterval(() => {
                        this.checkWindow();
                    }, 500);
            },
            propDisabled() {
                if (this.timer) return;
                this.timer = setTimeout(() => {
                    let flag = true;
                    if (
                        $('.summary input[name="variation_id"]').length &&
                        !$('.summary input[name="variation_id"]').val()
                    )
                        flag = false;

                    $(".clever-pay-check-out-express").prop(
                        "disabled",
                        flag ? false : true
                    );
                    this.timer = null;
                }, 200);
            },
            handleCashback(variation = "") {
                if ((typeof this.cashback_rate === "object" && !Object.keys(this.cashback_rate)) || this.cashback_rate === null){
                    
                    return;
                }
          


              switch(this.cashback_type) {

                case 'percentage':
                    this.showCashback('')
                  
                  break;


                  case 'absolute':
                    this.showCashback('')
                  break;
                  default:

              }

            },
            formatCashbackContent(content, cashback = 0){
                content = content?.replaceAll('${clever_name}', '<a href="https://helloclever.page.link/RtQw" target="_blank">Hello Clever app</a>')
                if(cashback)
                    content = content?.replaceAll('${cashback}', cashback.toFixed(2))
                return content
            },
            showCashback(save_price) {
              const { express, standard } = this.cashback_rate

              // express checkout
              if(!this.is_standard){
                if(express.hasOwnProperty('error')){
                    if (express.error?.message) {
                        $('.clever-pay-error').text(express.error.message)
                    }
                    switch(express.error.button_type){
                        case DISABLED:
                            $(".clever-pay-check-out-express").prop("disabled", true );
                            break;
                        case HIDDEN:
                            $(".clever-pay-check-out-express").hide();
                            break;
                    }
                    
                    return;
                }
              }

            //   if($('.cashback_title').length) {
            //     $('.cashback_title').text(express.title)
            //   }

              // reset value
              $('.cb-absolute .cb-content').html('');
              $('.cb-percentage .cb-content').html('');
              $(".cb-percentage").addClass("off-m");
              $(".cb-absolute").addClass("off-m");

              switch(this.cashback_type) {
                case 'percentage':
                    $('.cb-percentage .cb-content').html(`<span>${this.is_standard ? this.formatCashbackContent(standard.content_cashback, save_price) : this.formatCashbackContent(express.content_cashback, save_price)}</span>`)
                    $(".cb-percentage").removeClass("off-m");
                    $(".is-cashback").removeClass("off-m");
                  break;
                case 'absolute':
                    $('.cb-absolute .cb-content').html(`<span>${this.is_standard ? this.formatCashbackContent(standard.content_cashback) : this.formatCashbackContent(express.content_cashback)}</span>`)
                    $(".cb-absolute").removeClass("off-m");
                    $(".is-cashback").removeClass("off-m");

                  break;
                default:
                    $(".no-cashback").removeClass("off-m");
              }
                  
                if ($(".clever-pay-check-out-express img").length)
                    $(".clever-pay-check-out-express img").addClass("cl-has-cb");
            },
        
            tracking() {
                const ct_token = this.urlParams.searchParams.get("ct_token");
                if (ct_token) localStorage.setItem("ct_token", ct_token);

                if ($("#ct_token").length) {
                    let c = localStorage.getItem("ct_token");
                    $("#ct_token").val(c);
                }
            },
            checkStock() {
                const this_ = this;

                if ($('.summary input[name="variation_id"]').length > 0) {
                    const variations = $(".variations_form.cart").data(
                        "product_variations"
                    );
                    const variant_id = jQuery(
                        '.summary input[name="variation_id"]'
                    ).val();
                    const v = variations.find(
                        (variation) => variation.variation_id == variant_id
                    );
                    this_.out_of_stock = !v || (v && v.is_in_stock) ? false : true;
                }

                if (this_.out_of_stock) {
                    $(".clever-pay-check-out-express").addClass("cl-disabled");
                    $(".clever-pay-error").html(
                        `<img src="https://helloclever.co/static/plugins/assets/icon/warning.svg" width="25px"> Out of stock`
                    );
                    return;
                } else $(".clever-pay-check-out-express").removeClass("cl-disabled");
            },
            getCashbackV2(data){

                const this_ = this
                $.ajax({
                    url: this.configs.url + '/v1/ecom/get_cashback',
                    type: "POST",
                    dataType: "json",
                    headers: {
                        "app-id": this.configs.app_id,
                        "Content-Type": "application/json"
                    },
                    data: JSON.stringify(data),
                }).done(function(res) {
                    
                   
                    this_.cashback_rate = res;

                    if(res && 'absolute' in res && res.absolute && Object.keys(res.absolute).length > 0)
                        this_.cashback_type = 'absolute'
                    else if(res && res.cashback_rate)
                        this_.cashback_type = 'percentage'
                    else{
                        $('.cl-off-popup').hide()
                        return ;
                    }
                    $('.cl-off-popup').show()
                    this_.handleCashback();

                });

            },
            cashbackStandard(){
                const this_ = this
                if(!$(".woocommerce-checkout").length || $(".woocommerce-order-received").length)
                    return;

                const timer = setInterval(() => {
                    if($('#clever_cart_items').length){
                        clearInterval(timer)
                        const clever_cart_items = $('#clever_cart_items').length ? $('#clever_cart_items').data('json') : ''
                        this_.getCashbackV2(clever_cart_items)
                    }
                }, 1000)

                let timer_2 = null;
                $('body').on('update_checkout', function(){
                    if(timer_2)
                        clearTimeout(timer_2)
                    timer_2 = setTimeout(() => {
                        const clever_cart_items = $('#clever_cart_items').length ? $('#clever_cart_items').data('json') : ''
                        this_.getCashbackV2(clever_cart_items)
                   }, 500)
                })
          
            },
            cashbackExpress(){
                const this_ = this
                const is_variation = $('.summary input[name="variation_id"]').length

                if(!$(".single-product").length)
                    return;
                const product_info = $('#clever_product_info').data('json')

                const getCashbackSingleProduct = () => {
                    const quantity  = parseInt($(".summary .cart .qty").val());
                    let flag = true;
                    let clever_cart_items = null;

                    if(is_variation){
                        const variation_id = $("input.variation_id").val();
                        if(variation_id == 0){
                            flag = false;
                        }
                        const variations = $(".variations_form.cart").data(
                            "product_variations"
                        );
                        variations.forEach((variation) => {
                            if (variation.variation_id == variation_id) {
                                product_info.price = variation.display_price
                                clever_cart_items = {
                                    total_amount: quantity * variation.display_price,

                                    order_details: {
                                        items: [{...product_info, quantity}]
                                    },
                                    currency: product_info?.currency
                                }
                                this_.checkout_express_ready = true;
                            }
                        });
                    }
                    else{
                        clever_cart_items = {
                            total_amount: quantity * product_info?.price,
                            order_details: {
                                items: [{...product_info, quantity}]
                            },
                            currency: product_info?.currency
                        }
                        this_.checkout_express_ready = true;

                    }
                    if(flag){
                        this_.getCashbackV2(clever_cart_items)
                    }
                }

                $(".clever-pay-check-out-express").click(function(event) {
                    event.preventDefault();
                    if (!this_.checkout_express_ready) {
                        $(".clever-pay-error").text(this_.text.variation_empty);
                        return;
                    }

                    if (this_.out_of_stock) return;

                    const product_id = jQuery('.summary [name="add-to-cart"]').val();
                    const variation_id = jQuery('.summary input[name="variation_id"]')
                        .length ?
                        jQuery('.summary input[name="variation_id"]').val() :
                        "";
                    const quantity = jQuery(".summary .qty").val();
                    let attributes = "";
                    if(variation_id){
                        attributes = {}
                        $.each($('.variations select'), function(index, val) {
                            attributes[$(this).attr("name")] = $(this).val()
                        });
                    }
                    this_.create(product_id, quantity, variation_id, attributes);

                });

                if (is_variation) {

                    $(".variations_form").on(
                        "woocommerce_variation_select_change",
                        function() {
                            setTimeout(() => {
                                this_.checkStock();
                                const variation_id = $("input.variation_id").val();
                                this_.checkout_express_ready = variation_id ? true : false;
                                if (!variation_id) $(".cashback_value").addClass("off-m");
                            }, 100);
                            $(".clever-pay-error").text("");
                        }
                    );

                    $(".single_variation_wrap").on(
                        "show_variation",
                        function(event, variation) {
                           getCashbackSingleProduct()
                        }
                    );

                }

                getCashbackSingleProduct()
                $(".summary .cart .qty").change(() => {
                    getCashbackSingleProduct()
                });


            },
            offerPopup(){
                const this_ = this
                $('.cl-off-popup').click(function(e){
                    e.stopPropagation()

                    if (!this_.checkout_express_ready) {
                        $(".clever-pay-error").text(this_.text.variation_empty);
                        return;
                    }

                    if (this_.out_of_stock) return;
                    this_.LOADING(true);
                    const headers = {
                        "app-id": this_.configs.app_id,
                        "Plugin-Version": this_.configs.plugin_version,
                        "Content-Type": "application/json"
                    }
                    $.ajax({
                        url: this_.configs.url + '/v1/ecom/offer_iframe',
                        type: "POST",
                        dataType: "json",
                        headers: headers,
                        data: JSON.stringify(headers),
                    }).done(function(res) {
                        $("#clever-offer-iframe").attr(
                            "src",
                            `${res.iframe_url}`
                        );
                        $(".clever-loading-m").addClass("cl-express-checkout").removeClass('in')
                        MicroModal.show("clever-offer-popup", {
                            disableScroll: true,
                        });

                    });


                })
            },
            offerCreateTran(){
                const product_id = jQuery('.summary [name="add-to-cart"]').val();
                const variation_id = jQuery('.summary input[name="variation_id"]')
                    .length ?
                    jQuery('.summary input[name="variation_id"]').val() :
                    "";
                const quantity = jQuery(".summary .qty").val();
                let attributes = "";
                if(variation_id){
                    attributes = {}
                    $.each($('.variations select'), function(index, val) {
                        attributes[$(this).attr("name")] = $(this).val()
                    });
                }
                this.create(product_id, quantity, variation_id, attributes, true);
            },
            eventListener(){
                const this_ = this
                window.addEventListener("message", function(event) {
                    if (event.data.event_id === "close-popup") {
                        toggleExpressCheckoutPopup(false);
                    }
                    if (event.data.event_id === "redirect") {
                        window.location.href = event.data.url;
                    }
                    if (event.data.event_id === "express_checkout") {
                        this_.offerCreateTran()
                    }
                });
            },
            init() {
                const this_ = this;

                $(".cl-popup-show").click(function() {
                    MicroModal.show("clever-express-checkout-popup");
                });

                $(".cl-close-popup").click(function() {
                    $(".clever-loading-m").removeClass("in cl-express-checkout");
                    MicroModal.close("clever-express-checkout-popup");
                    MicroModal.close("clever-offer-popup");
                });


                if($(".woocommerce-checkout").length)
                    this.is_standard = true

            
                this.cashbackExpress();
                this.cashbackStandard();
                this.tracking();
                this.propDisabled();
                this.offerPopup();
                this.eventListener()
            },
        };

        checkout.init();
    });
})(jQuery);