( function( wp ) {
    const { createElement, useState, useEffect } = wp.element;
    const { registerPaymentMethod }      = window.wc.wcBlocksRegistry;
    const { __ }                         = wp.i18n;
    const { SelectControl }              = wp.components;

    // Recorremos todas las configuraciones cargadas por PHP
    Object.keys( window ).forEach( key => {
        if ( ! key.startsWith( 'paylandsPaymentMethod_' ) ) {
            return;
        }

        const settings    = window[ key ];
        const gatewayName = settings.name;
        const title       = settings.title       || __( 'Paylands', 'woocommerce-paylands' );
        const description = settings.description || '';
        const icon        = settings.icon;
        // mantenemos el array supports tal cual vino de PHP
        const supports    = Array.isArray( settings.supports ) ? settings.supports : [];

        // --- Componente Label (icono + título) idéntico para todos ---
        const Label = () => createElement(
            'div',
            { className: 'paylands-payment-label' },
            createElement( 'span', null, title ),
            icon &&
                createElement( 'img', {
                    src:   icon,
                    alt:   __( 'Icon', 'woocommerce-paylands' ),
                    style: { marginLeft: '10px', maxHeight: '30px' },
                } )
        );

        // --- Componente genérico (redirect message) ---
        const GenericContent = () => createElement(
            'div',
            { className: 'paylands-payment-content' },
            createElement(
                'p',
                null,
                description || __( 'You will be redirected to complete the payment.', 'woocommerce-paylands' )
            )
        );

        // --- Componente One-Click, con SelectControl + onPaymentProcessing ---
        const OneClickContent = ( props ) => {
            const { eventRegistration, emitResponse } = props;
            const [ selected, setSelected ] = useState( settings.cards[0]?.value || '' );

            // Al montar, nos subscribimos a onPaymentProcessing
            useEffect( () => {
                const unsubscribe = eventRegistration.onPaymentProcessing( () => {
                    if ( ! selected ) {
                        return {
                            type:    emitResponse.responseTypes.ERROR,
                            message: __( 'Please select a card.', 'woocommerce-paylands' ),
                        };
                    }
                    // Al devolver SUCCESS + paymentMethodData, Blocks lo mete en payment_data[]
                    return {
                        type: emitResponse.responseTypes.SUCCESS,
                        meta: {
                            paymentMethodData: {
                                paylands_saved_card_token: selected,
                            },
                        },
                    };
                } );
                return () => unsubscribe();
            }, [ selected, eventRegistration, emitResponse ] );

            return createElement(
                'div',
                { className: 'paylands-payment-content' },
                createElement(
                    'p',
                    null,
                    description || __( 'Select a saved card for a faster one-click payment', 'woocommerce-paylands' )
                ),
                createElement( SelectControl, {
                    value:    selected,
                    options:  settings.cards.map( card => ( {
                        label: `${ card.brand } **** ${ card.last4 } (${ card.expiry })`,
                        value: card.value,
                    } ) ),
                    onChange: setSelected,
                } )
            );
        };

        // --- Registramos el método, alternando según sea One-Click o no ---
        registerPaymentMethod( {
            name:           gatewayName,
            label:          createElement( Label ),
            content:        gatewayName === 'paylands_woocommerce_one_click'
                                ? createElement( OneClickContent )
                                : createElement( GenericContent ),
            edit:           gatewayName === 'paylands_woocommerce_one_click'
                                ? createElement( OneClickContent )
                                : createElement( GenericContent ),
            canMakePayment: () => true,
            ariaLabel:      title,
            supports:       { features: supports },
        } );
    } );

    // --- Lógica de mostrar error en checkout (idéntica a la tuya) ---
    function showPaylandsError() {
        const params = new URLSearchParams( window.location.search );
        if ( params.has( 'paylands_error' ) ) {
            let msg = '';
            Object.keys( window ).some( key => {
                if ( key.startsWith( 'paylandsPaymentMethod_' ) ) {
                    const s = window[ key ];
                    if ( s.error_message ) {
                        msg = s.error_message;
                        return true;
                    }
                }
                return false;
            } );
            if ( msg ) {
                const form = document.querySelector( '.wp-block-woocommerce-checkout' );
                if ( form ) {
                    const div = document.createElement( 'div' );
                    div.className = 'wc-block-components-notices';
                    div.innerHTML = `
                        <div class="wc-block-store-notice wc-block-components-notice-banner is-error is-dismissible">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24">
                                <path d="M12 3.2c-4.8 0-8.8 3.9-8.8 8.8 0 4.8 3.9 8.8 8.8 8.8 4.8 0 8.8-3.9 8.8-8.8 0-4.8-4-8.8-8.8-8.8zm0 16c-4 0-7.2-3.3-7.2-7.2C4.8 8 8 4.8 12 4.8s7.2 3.3 7.2 7.2c0 4-3.2 7.2-7.2 7.2zM11 17h2v-6h-2v6zm0-8h2V7h-2v2z"/>
                            </svg>
                            <div class="wc-block-components-notice-banner__content">
                                <div>${ msg }</div>
                            </div>
                            <button class="wc-block-components-button wp-element-button wc-block-components-notice-banner__dismiss" type="button">×</button>
                        </div>`;
                    form.insertBefore( div, form.firstChild );
                    div.scrollIntoView( { behavior: 'smooth', block: 'start' } );
                    div.querySelector( '.wc-block-components-notice-banner__dismiss' )
                       .addEventListener( 'click', () => div.remove() );
                }
            }
            const u = new URL( window.location.href );
            u.searchParams.delete( 'paylands_error' );
            window.history.replaceState( {}, '', u );
        }
    }
    if ( document.readyState === 'loading' ) {
        document.addEventListener( 'DOMContentLoaded', showPaylandsError );
    } else {
        showPaylandsError();
    }

} )( window.wp );
