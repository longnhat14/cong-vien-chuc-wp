/* Văn bản pháp luật v2 (19/10/2026): bộ lọc tự áp dụng, đồng bộ 2 ô từ khóa, thu gọn bộ lọc trên điện thoại. */
( function () {
	'use strict';
	var form = document.getElementById( 'tra-cuu' );
	if ( ! form ) {
		return;
	}
	var typeSel = document.getElementById( 'lg-type' );
	form.addEventListener( 'change', function ( e ) {
		var t = e.target;
		if ( t.name === 'loai[]' && typeSel ) {
			typeSel.value = '';
		}
		if ( t === typeSel ) {
			form.querySelectorAll( '[name="loai[]"]' ).forEach( function ( c ) {
				c.checked = false;
			} );
			return;
		}
		if ( t.hasAttribute( 'data-autosubmit' ) ) {
			form.submit();
		}
	} );
	form.querySelectorAll( '[data-sync]' ).forEach( function ( inp ) {
		inp.addEventListener( 'input', function () {
			var other = document.getElementById( inp.getAttribute( 'data-sync' ) );
			if ( other ) {
				other.value = inp.value;
			}
		} );
	} );
	var side = document.getElementById( 'lg-q-side' );
	if ( side ) {
		side.addEventListener( 'keydown', function ( e ) {
			if ( e.key === 'Enter' ) {
				e.preventDefault();
				form.submit();
			}
		} );
	}
	var head = form.querySelector( '.lg-filterhead' );
	var box = document.getElementById( 'lg-filters' );
	if ( head && box ) {
		head.addEventListener( 'click', function () {
			var open = box.classList.toggle( 'is-open' );
			head.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
		} );
	}
} )();
