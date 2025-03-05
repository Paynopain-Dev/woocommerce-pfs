( function( wp ) {
    const { createElement } = wp.element;
    const { registerPaymentMethod } = window.wc.wcBlocksRegistry;
    const { __ } = wp.i18n;

    // Registrar cada configuración de pasarela que se encuentre en window.
    Object.keys(window).forEach( function(key) {
        if ( key.startsWith("paylandsPaymentMethod_") ) {
            const paylandsSettings = window[key];
            const gatewayName = paylandsSettings.name;

            // Componente para mostrar el label del método de pago.
            const PaylandsPaymentLabel = () => {
                return createElement(
                    'div',
                    { className: 'paylands-payment-label' },
                    createElement(
                        'span',
                        null,
                        paylandsSettings.title || __( 'Método de pago', 'woocommerce-paylands' )
                    ),
                    paylandsSettings.icon &&
                        createElement( 'img', {
                            src: paylandsSettings.icon,
                            alt: __( 'Ícono', 'woocommerce-paylands' ),
                            style: { marginLeft: '10px', maxHeight: '30px' }
                        } )
                );
            };

            // Componente que muestra el contenido (mensaje informativo de redirección).
            const PaylandsPaymentContent = () => {
                return createElement(
                    'div',
                    { className: 'paylands-payment-content' },
                    createElement(
                        'p',
                        null,
                        paylandsSettings.description || __( 'Serás redirigido para completar el pago.', 'woocommerce-paylands' )
                    )
                );
            };

            // Registro del método de pago en WooCommerce Blocks.
            registerPaymentMethod({
                name: gatewayName,
                label: createElement( PaylandsPaymentLabel ),
                content: createElement( PaylandsPaymentContent ),
                edit: createElement( PaylandsPaymentContent ),
                canMakePayment: () => true,
                ariaLabel: paylandsSettings.title || __( 'Método de pago', 'woocommerce-paylands' ),
                supports: {
                    features: paylandsSettings.supports || []
                }
            });
        }
    });

    // Función para mostrar el error en el checkout.
    function showPaylandsError() {
        const urlParams = new URLSearchParams(window.location.search);
        if ( urlParams.has("paylands_error") ) {
            // En lugar de leer el mensaje desde la URL, usamos el error_message
            // de la configuración del método de pago (tomamos el primero que se encuentre).
            let errorMessage = "";
            Object.keys(window).forEach( function(key) {
                if ( key.startsWith("paylandsPaymentMethod_") && !errorMessage ) {
                    const settings = window[key];
                    if ( settings.error_message ) {
                        errorMessage = settings.error_message;
                    }
                }
            });
            
            if ( errorMessage ) {
                const checkoutForm = document.querySelector(".wp-block-woocommerce-checkout");
                if ( checkoutForm ) {
                    // Crear la estructura del mensaje de error.
                    const errorDiv = document.createElement("div");
                    errorDiv.className = "wc-block-components-notices";
                    errorDiv.innerHTML = `
                        <div class="wc-block-store-notice wc-block-components-notice-banner is-error is-dismissible">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false">
                                <path d="M12 3.2c-4.8 0-8.8 3.9-8.8 8.8 0 4.8 3.9 8.8 8.8 8.8 4.8 0 8.8-3.9 8.8-8.8 0-4.8-4-8.8-8.8-8.8zm0 16c-4 0-7.2-3.3-7.2-7.2C4.8 8 8 4.8 12 4.8s7.2 3.3 7.2 7.2c0 4-3.2 7.2-7.2 7.2zM11 17h2v-6h-2v6zm0-8h2V7h-2v2z"></path>
                            </svg>
                            <div class="wc-block-components-notice-banner__content">
                                <div>${errorMessage}</div>
                            </div>
                            <button class="wc-block-components-button wp-element-button wc-block-components-notice-banner__dismiss contained" aria-label="Descartar este aviso" type="button">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false">
                                    <path d="M13 11.8l6.1-6.3-1-1-6.1 6.2-6.1-6.2-1 1 6.1 6.3-6.5 6.7 1 1 6.5-6.6 6.5 6.6 1-1z"></path>
                                </svg>
                            </button>
                        </div>
                    `;

                    // Insertar el mensaje de error al inicio del formulario de checkout.
                    checkoutForm.insertBefore(errorDiv, checkoutForm.firstChild);
                    errorDiv.scrollIntoView({ behavior: "smooth", block: "start" });

                    // Agregar el listener para poder descartar el aviso.
                    const dismissButton = errorDiv.querySelector(".wc-block-components-notice-banner__dismiss");
                    if ( dismissButton ) {
                        dismissButton.addEventListener("click", () => {
                            errorDiv.remove();
                        });
                    }
                }
            }
            // Eliminar el parámetro de error de la URL.
            const url = new URL(window.location.href);
            url.searchParams.delete("paylands_error");
            window.history.replaceState({}, "", url);
        }
    }

    // Ejecutar la función cuando el DOM esté listo.
    if ( document.readyState === "loading" ) {
        document.addEventListener("DOMContentLoaded", showPaylandsError);
    } else {
        showPaylandsError();
    }
} )( window.wp );
