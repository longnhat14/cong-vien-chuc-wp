/*
 * Chi tiết tuyển dụng v4 (18/10/2026): tab nội dung, lọc bảng vị trí, chọn tệp xem trước,
 * tìm trong văn bản (AJAX cvc_rd_search), đếm ngược hạn nộp, chia sẻ. Không phụ thuộc thư viện.
 */
( function () {
	'use strict';
	var root = document.querySelector( 'main.rd' );
	if ( ! root ) {
		return;
	}
	var cfg = window.CVC_RD || {};

	/* ---- Tab nội dung văn bản ---- */
	var tabs = root.querySelectorAll( '[data-rd-tab]' );
	function openTab( id, focus ) {
		var found = false;
		tabs.forEach( function ( b ) {
			var on = b.getAttribute( 'data-rd-tab' ) === id;
			found = found || on;
			b.classList.toggle( 'is-cur', on );
			b.setAttribute( 'aria-selected', on ? 'true' : 'false' );
			b.tabIndex = on ? 0 : -1;
			var p = document.getElementById( b.getAttribute( 'data-rd-tab' ) );
			if ( p ) {
				p.hidden = ! on;
			}
			if ( on && focus ) {
				b.focus();
			}
		} );
		return found;
	}
	tabs.forEach( function ( b, i ) {
		b.addEventListener( 'click', function () {
			openTab( b.getAttribute( 'data-rd-tab' ) );
		} );
		b.addEventListener( 'keydown', function ( e ) {
			if ( 'ArrowRight' !== e.key && 'ArrowLeft' !== e.key ) {
				return;
			}
			var n = tabs[ ( i + ( 'ArrowRight' === e.key ? 1 : tabs.length - 1 ) ) % tabs.length ];
			openTab( n.getAttribute( 'data-rd-tab' ), true );
		} );
	} );
	function fromHash() {
		var id = ( location.hash || '' ).slice( 1 );
		if ( id && openTab( id ) ) {
			var el = document.getElementById( 'noi-dung-van-ban' );
			if ( el ) {
				el.scrollIntoView();
			}
		}
	}
	window.addEventListener( 'hashchange', fromHash );
	fromHash();
	root.addEventListener( 'click', function ( e ) {
		var a = e.target.closest( 'a[href^="#"]' );
		if ( ! a ) {
			return;
		}
		var id = a.getAttribute( 'href' ).slice( 1 );
		if ( id && document.querySelector( '[data-rd-tab="' + id + '"]' ) ) {
			e.preventDefault();
			openTab( id );
			document.getElementById( 'noi-dung-van-ban' ).scrollIntoView( { behavior: 'smooth' } );
			history.replaceState( null, '', '#' + id );
		}
	} );

	/* ---- Lọc bảng vị trí ---- */
	var q = document.getElementById( 'rd-pos-q' );
	var g = document.getElementById( 'rd-pos-g' );
	var rows = root.querySelectorAll( '#rd-pos-table tbody tr' );
	var empty = document.getElementById( 'rd-pos-empty' );
	function norm( s ) {
		return ( s || '' ).toLowerCase().normalize( 'NFC' ).trim();
	}
	function filter() {
		var t = norm( q ? q.value : '' );
		var gv = g ? g.value : '';
		var shown = 0;
		rows.forEach( function ( r ) {
			var ok = ( ! t || -1 !== ( r.getAttribute( 'data-q' ) || '' ).indexOf( t ) ) && ( ! gv || r.getAttribute( 'data-g' ) === gv );
			r.hidden = ! ok;
			shown += ok ? 1 : 0;
		} );
		if ( empty ) {
			empty.hidden = shown > 0;
		}
	}
	if ( q ) {
		q.addEventListener( 'input', filter );
	}
	if ( g ) {
		g.addEventListener( 'change', filter );
	}

	/* ---- Chọn tệp xem trước ---- */
	var files = root.querySelectorAll( '[data-rd-file]' );
	files.forEach( function ( b ) {
		b.addEventListener( 'click', function () {
			var i = b.getAttribute( 'data-rd-file' );
			files.forEach( function ( x ) {
				var on = x === b;
				x.classList.toggle( 'is-cur', on );
				x.setAttribute( 'aria-selected', on ? 'true' : 'false' );
			} );
			root.querySelectorAll( '.rd-prev' ).forEach( function ( p ) {
				p.hidden = p.id !== 'rd-prev-' + i;
			} );
			var fr = document.querySelector( '#rd-prev-' + i + ' iframe[data-src]' );
			if ( fr ) {
				fr.src = fr.getAttribute( 'data-src' );
				fr.removeAttribute( 'data-src' );
			}
		} );
	} );

	/* ---- Tìm trong văn bản ---- */
	var form = root.querySelector( '[data-rd-find]' );
	var out = document.getElementById( 'rd-find-res' );
	if ( form && out && cfg.ajax ) {
		form.addEventListener( 'submit', function ( e ) {
			e.preventDefault();
			var term = ( form.q.value || '' ).trim();
			if ( term.length < 2 ) {
				out.innerHTML = '<p class="rd-note">Nhập ít nhất 2 ký tự.</p>';
				return;
			}
			out.classList.add( 'is-loading' );
			out.innerHTML = '<p class="rd-note">Đang tìm…</p>';
			var url = cfg.ajax + '?action=cvc_rd_search&slug=' + encodeURIComponent( root.getAttribute( 'data-rd-slug' ) || '' ) + '&q=' + encodeURIComponent( term );
			fetch( url, { credentials: 'same-origin' } )
				.then( function ( r ) {
					return r.json();
				} )
				.then( function ( j ) {
					out.innerHTML = j && j.success ? j.data.html : '<p class="rd-note">' + ( ( j && j.data && j.data.message ) || 'Không tìm được lúc này.' ) + '</p>';
				} )
				.catch( function () {
					out.innerHTML = '<p class="rd-note">Không kết nối được, vui lòng thử lại.</p>';
				} )
				.then( function () {
					out.classList.remove( 'is-loading' );
				} );
		} );
	}

	/* ---- Đếm ngược hạn nộp ---- */
	var cd = root.querySelector( '[data-rd-deadline]' );
	if ( cd ) {
		var end = Date.parse( cd.getAttribute( 'data-rd-deadline' ) );
		var tick = function () {
			var ms = Math.max( 0, end - Date.now() );
			var d = Math.floor( ms / 864e5 );
			var h = Math.floor( ( ms % 864e5 ) / 36e5 );
			var m = Math.floor( ( ms % 36e5 ) / 6e4 );
			cd.querySelector( '[data-u="d"]' ).textContent = d;
			cd.querySelector( '[data-u="h"]' ).textContent = h;
			cd.querySelector( '[data-u="m"]' ).textContent = m;
		};
		if ( ! isNaN( end ) ) {
			tick();
			setInterval( tick, 30000 );
		}
	}

	/* ---- Số người đã xem + tin tuyển dụng gần bạn (18/10/2026) ---- */
	var nearBox = root.querySelector( '[data-rd-near] .rd-near-body' );
	var viewsEl = root.querySelector( '[data-rd-views]' );
	var rid = parseInt( root.getAttribute( 'data-rd-id' ) || '0', 10 );
	function visitorId() {
		try {
			var v = localStorage.getItem( 'cvc_vid' );
			if ( ! v ) {
				v = ( window.crypto && crypto.randomUUID ? crypto.randomUUID().replace( /-/g, '' ) : Math.random().toString( 36 ).slice( 2 ) + Date.now().toString( 36 ) );
				localStorage.setItem( 'cvc_vid', v );
			}
			return v;
		} catch ( e ) {
			return '';
		}
	}
	function chosenProvince() {
		var m = document.cookie.match( /(?:^|;\s*)cvc_tinh=([a-z0-9-]+)/ );
		return m ? m[ 1 ] : '';
	}
	function loadCtx( withView ) {
		if ( ! cfg.ajax || ( ! nearBox && ! ( viewsEl && rid ) ) ) {
			return;
		}
		var fd = new FormData();
		fd.append( 'action', 'cvc_rd_ctx' );
		fd.append( 'id', String( rid || 0 ) );
		fd.append( 'vid', visitorId() );
		fd.append( 'tinh', chosenProvince() );
		if ( withView && rid ) {
			fd.append( 'view', '1' );
		}
		fetch( cfg.ajax, { method: 'POST', body: fd, credentials: 'same-origin' } )
			.then( function ( r ) {
				return r.json();
			} )
			.then( function ( j ) {
				if ( ! j || ! j.success ) {
					throw new Error( 'ctx' );
				}
				if ( viewsEl && null !== j.data.viewers && j.data.viewers > 0 ) {
					viewsEl.querySelector( 'b' ).textContent = j.data.viewers.toLocaleString( 'vi-VN' );
					viewsEl.hidden = false;
				}
				if ( nearBox && j.data.near_html ) {
					nearBox.innerHTML = j.data.near_html;
				}
			} )
			.catch( function () {
				if ( nearBox ) {
					nearBox.innerHTML = '<p class="rd-small rd-muted">Chưa tải được tin gần bạn.</p>';
				}
			} );
	}
	if ( nearBox ) {
		nearBox.addEventListener( 'change', function ( e ) {
			var sel = e.target.closest( '[data-rd-tinh]' );
			if ( ! sel ) {
				return;
			}
			document.cookie = 'cvc_tinh=' + ( sel.value || '' ) + ';path=/;max-age=' + ( sel.value ? 15552000 : 0 ) + ';SameSite=Lax';
			nearBox.innerHTML = '<p class="rd-small rd-muted">Đang tải…</p>';
			loadCtx( false );
		} );
	}
	loadCtx( true );

	/* ---- Chia sẻ ---- */
	root.querySelectorAll( '[data-rd-share]' ).forEach( function ( b ) {
		b.addEventListener( 'click', function () {
			var u = b.getAttribute( 'data-url' ) || location.href;
			var t = b.getAttribute( 'data-title' ) || document.title;
			if ( navigator.share ) {
				navigator.share( { title: t, url: u } ).catch( function () {} );
				return;
			}
			var done = function () {
				var old = b.textContent;
				b.textContent = 'Đã chép liên kết ✓';
				setTimeout( function () {
					b.textContent = old;
				}, 2200 );
			};
			if ( navigator.clipboard ) {
				navigator.clipboard.writeText( u ).then( done, function () {
					window.prompt( 'Chép liên kết:', u );
				} );
			} else {
				window.prompt( 'Chép liên kết:', u );
			}
		} );
	} );
} )();
