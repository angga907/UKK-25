<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'kasir') {
	header("Location: ../login.php?role=kasir");
	exit;
}

include "../koneksi.php";

// Detect if menu table has status and stock
$hasStatus = false; $hasStock = false;
$cols = mysqli_query($koneksi, "SHOW COLUMNS FROM menu");
while($c = mysqli_fetch_assoc($cols)){
	if ($c['Field'] === 'status') $hasStatus = true;
	if ($c['Field'] === 'stok') $hasStock = true;
}

$selectFields = "id, nama_menu, kategori, harga, deskripsi";
if ($hasStatus) { $selectFields .= ", status"; }
if ($hasStock) { $selectFields .= ", stok"; }
$menus = mysqli_query($koneksi, "SELECT $selectFields FROM menu ORDER BY kategori, nama_menu");
?>
<!DOCTYPE html>
<html lang="id">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Kasir - KasirKita</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
	<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
	<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
	<style>
		body { font-family:'Poppins', sans-serif; background:#f7f8fb; }
		.container-app { padding:20px; }
		.menu-card { border:none; border-radius:14px; box-shadow:0 6px 18px rgba(0,0,0,.06); transition:.2s; }
		.menu-card:hover { transform: translateY(-3px); box-shadow:0 10px 20px rgba(0,0,0,.1); }
		.cart { position: sticky; top:20px; }
		.badge-status { border-radius:12px; padding:4px 10px; font-size:.75rem; }
		.badge-stok { border-radius:12px; padding:4px 10px; font-size:.75rem; }
	</style>
</head>
<body>
	<div class="container-app container-fluid">
		<div class="d-flex justify-content-between align-items-center mb-3">
			<h3 class="mb-0"><i class="fas fa-cash-register me-2"></i>Kasir</h3>
			<div class="d-flex gap-2">
				<button class="btn btn-outline-info btn-sm" onclick="debugLog('Test button clicked'); alert('JavaScript berfungsi!');">
					<i class="fas fa-bug me-1"></i>Test JS
				</button>
				<a href="../index.php" class="btn btn-outline-secondary"><i class="fas fa-home me-2"></i>Home</a>
			</div>
		</div>

		<div class="row g-3">
			<div class="col-lg-8">
				<div class="row g-3">
					<?php while($m = mysqli_fetch_assoc($menus)): $disabled = ($hasStatus && $m['status']!=='aktif') || ($hasStock && (int)$m['stok']<=0); ?>
					<div class="col-md-6 col-xl-4">
						<div class="card menu-card h-100">
							<div class="card-body d-flex flex-column">
								<div class="d-flex justify-content-between align-items-start">
									<h6 class="fw-semibold mb-1"><?php echo htmlspecialchars($m['nama_menu']); ?></h6>
									<div class="d-flex gap-1">
										<?php if ($hasStatus): ?>
										<span class="badge-status <?php echo $m['status']==='aktif'?'bg-success-subtle text-success':'bg-secondary-subtle text-secondary'; ?>"><?php echo ucfirst($m['status']); ?></span>
										<?php endif; ?>
										<?php if ($hasStock): ?>
										<span class="badge-stok <?php echo (int)$m['stok']>0 ? 'bg-primary-subtle text-primary':'bg-danger-subtle text-danger'; ?>">Stok: <?php echo (int)$m['stok']; ?></span>
										<?php endif; ?>
									</div>
								</div>
								<small class="text-muted mb-2"><?php echo htmlspecialchars($m['deskripsi'] ?? ''); ?></small>
								<div class="mt-auto d-flex justify-content-between align-items-center">
									<strong>Rp <?php echo number_format($m['harga'],0,',','.'); ?></strong>
									<button class="btn btn-sm btn-primary add-to-cart-btn" 
										<?php echo $disabled?'disabled':''; ?> 
										data-id="<?php echo $m['id']; ?>"
										data-name="<?php echo htmlspecialchars($m['nama_menu']); ?>"
										data-price="<?php echo (int)$m['harga']; ?>"
										data-stok="<?php echo $hasStock ? (int)$m['stok'] : ''; ?>">
										<i class="fas fa-plus me-1"></i>Tambah
									</button>
								</div>
							</div>
						</div>
					</div>
					<?php endwhile; ?>
				</div>
			</div>

			<div class="col-lg-4">
				<div class="card cart">
					<div class="card-header">
						<h6 class="mb-0"><i class="fas fa-shopping-cart me-2"></i>Keranjang</h6>
					</div>
					<div class="card-body">
						<div class="mb-3">
							<label class="form-label">Nomor Meja</label>
							<input type="number" id="table-number" class="form-control" placeholder="Contoh: 5">
						</div>
						<div class="mb-3">
							<label class="form-label">Nama Pelanggan</label>
							<input type="text" id="customer-name" class="form-control" placeholder="Tamu">
						</div>
						<div id="cart-list"></div>
						<hr>
						<div class="d-flex justify-content-between">
							<strong>Total</strong>
							<strong id="cart-total">Rp 0</strong>
						</div>
					</div>
					<div class="card-footer d-grid">
						<button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#cashModal"><i class="fas fa-money-bill-wave me-2"></i>Bayar Tunai</button>
					</div>
				</div>
			</div>
		</div>
	</div>

	<!-- Cash Modal -->
	<div class="modal fade" id="cashModal" tabindex="-1">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title"><i class="fas fa-money-bill-wave me-2"></i>Pembayaran Tunai</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
				</div>
				<div class="modal-body">
					<div class="mb-3">
						<label class="form-label">Total Pembayaran</label>
						<input type="text" id="modal-total" class="form-control" readonly>
					</div>
					<div class="mb-3">
						<label class="form-label">Uang Diterima</label>
						<input type="number" id="modal-paid" class="form-control" placeholder="Masukkan nominal uang" min="0">
					</div>
					<div class="mb-3">
						<div class="alert alert-info">
							<i class="fas fa-info-circle me-2"></i>
							<strong>Kembalian: <span id="modal-change">Rp 0</span></strong>
						</div>
					</div>
					<div class="mb-3">
						<label class="form-label">Nomor Meja</label>
						<input type="number" id="modal-table" class="form-control" placeholder="Nomor meja" min="1">
					</div>
					<div class="mb-3">
						<label class="form-label">Nama Pelanggan</label>
						<input type="text" id="modal-customer" class="form-control" placeholder="Nama pelanggan">
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
					<button type="button" class="btn btn-success" onclick="confirmCash()">
						<i class="fas fa-check me-2"></i>Konfirmasi Pembayaran
					</button>
				</div>
			</div>
		</div>
	</div>

	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
	<script>
		// Global cart array
		const cart = [];
		
		// Debug function
		function debugLog(message) {
			console.log('[Kasir Debug]:', message);
		}
		
		// Add to cart function
		function addToCart(item) {
			debugLog('addToCart called with:', item);
			
			// Check if item is valid
			if (!item || !item.id || !item.name || !item.price) {
				alert('Data menu tidak valid!');
				return;
			}
			
			// Enforce stock
			if (item.stok !== null && item.stok !== undefined) {
				const used = cart.find(i => i.id === item.id)?.qty || 0;
				if (used >= item.stok) { 
					alert('Stok tidak cukup! Stok tersisa: ' + item.stok); 
					return; 
				}
			}
			
			// Add or update item in cart
			const existing = cart.find(i => i.id === item.id);
			if (existing) { 
				existing.qty += 1; 
				debugLog('Updated existing item:', existing);
			} else { 
				cart.push({...item, qty: 1}); 
				debugLog('Added new item to cart:', cart[cart.length - 1]);
			}
			
			renderCart();
		}
		function changeQty(id, delta){
			const it = cart.find(i => i.id === id);
			if(!it) return; 
			const newQty = it.qty + delta;
			if(newQty <= 0){ const idx = cart.indexOf(it); cart.splice(idx,1); renderCart(); return; }
			if(it.stok !== null && it.stok !== undefined && newQty > it.stok){ alert('Stok tidak cukup'); return; }
			it.qty = newQty; renderCart();
		}
		function renderCart() {
			debugLog('renderCart called, cart items:', cart);
			
			let html = ''; 
			let total = 0;
			
			cart.forEach(i => {
				total += i.price * i.qty;
				html += `<div class="d-flex justify-content-between align-items-center mb-2">
					<div>
						<div class="fw-semibold">${i.name}</div>
						<small class="text-muted">Rp ${i.price.toLocaleString('id-ID')}${i.stok != null ? ` • Stok: ${i.stok - i.qty}` : ''}</small>
					</div>
					<div class="d-flex align-items-center gap-2">
						<button class="btn btn-sm btn-outline-secondary qty-btn" data-id="${i.id}" data-delta="-1">-</button>
						<span>${i.qty}</span>
						<button class="btn btn-sm btn-outline-secondary qty-btn" data-id="${i.id}" data-delta="1">+</button>
					</div>
				</div>`;
			});
			
			// Update cart display
			const cartListElement = document.getElementById('cart-list');
			const cartTotalElement = document.getElementById('cart-total');
			
			if (cartListElement) {
				cartListElement.innerHTML = html || '<div class="text-muted">Keranjang kosong</div>';
			} else {
				debugLog('ERROR: cart-list element not found!');
			}
			
			if (cartTotalElement) {
				cartTotalElement.innerText = 'Rp ' + total.toLocaleString('id-ID');
			} else {
				debugLog('ERROR: cart-total element not found!');
			}
			
			// Update modal total
			const modalTotal = document.getElementById('modal-total'); 
			if (modalTotal) { 
				modalTotal.value = 'Rp ' + total.toLocaleString('id-ID'); 
			}
			
			// Setup paid input handler
			const paidInput = document.getElementById('modal-paid');
			if (paidInput) {
				paidInput.oninput = () => {
					const paid = parseInt(paidInput.value || 0);
					const change = Math.max(0, paid - total);
					const changeElement = document.getElementById('modal-change');
					if (changeElement) {
						changeElement.innerText = 'Rp ' + change.toLocaleString('id-ID');
					}
				};
			}
			
			debugLog('Cart rendered successfully, total:', total);
		}

		// Initialize when DOM is loaded
		document.addEventListener('DOMContentLoaded', function() {
			debugLog('DOM loaded, initializing...');
			
			// Test if elements exist
			const cartListElement = document.getElementById('cart-list');
			const cartTotalElement = document.getElementById('cart-total');
			
			debugLog('Cart elements found:', {
				cartList: !!cartListElement,
				cartTotal: !!cartTotalElement
			});
			
			// Initialize cart display
			renderCart();
			
			// Event delegation for add to cart buttons
			document.addEventListener('click', function(e) {
				if (e.target.closest('.add-to-cart-btn')) {
					e.preventDefault();
					const btn = e.target.closest('.add-to-cart-btn');
					
					if (btn.disabled) {
						alert('Menu tidak tersedia!');
						return;
					}
					
					const item = {
						id: parseInt(btn.dataset.id),
						name: btn.dataset.name,
						price: parseInt(btn.dataset.price),
						stok: btn.dataset.stok ? parseInt(btn.dataset.stok) : null
					};
					
					debugLog('Add to cart clicked:', item);
					addToCart(item);
				}
			});
			
			// Event delegation for quantity change buttons
			document.addEventListener('click', function(e) {
				if (e.target.closest('.qty-btn')) {
					e.preventDefault();
					const btn = e.target.closest('.qty-btn');
					const id = parseInt(btn.dataset.id);
					const delta = parseInt(btn.dataset.delta);
					
					debugLog('Quantity change clicked:', {id, delta});
					changeQty(id, delta);
				}
			});
			
			// Event listener untuk modal
			const cashModal = document.getElementById('cashModal');
			if (cashModal) {
				cashModal.addEventListener('show.bs.modal', function () {
					debugLog('Modal opened');
					// Copy data dari form utama ke modal
					const tableNumber = document.getElementById('table-number').value;
					const customerName = document.getElementById('customer-name').value;
					
					document.getElementById('modal-table').value = tableNumber;
					document.getElementById('modal-customer').value = customerName;
					
					// Reset paid amount
					document.getElementById('modal-paid').value = '';
					document.getElementById('modal-change').innerText = 'Rp 0';
				});
			}
			
			// Test addToCart function
			debugLog('addToCart function available:', typeof addToCart);
			debugLog('renderCart function available:', typeof renderCart);
		});
		
		// Make functions globally available
		window.addToCart = addToCart;
		window.changeQty = changeQty;
		window.renderCart = renderCart;
		window.confirmCash = confirmCash;

		async function confirmCash(){
			let total = 0; cart.forEach(i=> total += i.price*i.qty);
			const paid = parseInt(document.getElementById('modal-paid').value||0);
			const table = parseInt(document.getElementById('modal-table').value||0);
			const customer = document.getElementById('modal-customer').value || 'Tamu';
			
			if(cart.length === 0) {
				alert('Keranjang kosong!');
				return;
			}
			
			if(paid < total){ 
				alert('Uang diterima kurang dari total. Kekurangan: Rp ' + (total - paid).toLocaleString('id-ID')); 
				return; 
			}
			
			if(table <= 0) {
				alert('Masukkan nomor meja yang valid!');
				return;
			}
			
			try{
				const res = await fetch('create_cash_order.php', {
					method:'POST', 
					headers:{'Content-Type':'application/json'}, 
					body: JSON.stringify({items:cart, table, customer, paid})
				});
				
				const data = await res.json();
				
				if(data.error) {
					alert('Error: ' + data.error);
					return;
				}
				
				if(data.success && data.orderId) {
					// Close modal
					const modal = bootstrap.Modal.getInstance(document.getElementById('cashModal'));
					modal.hide();
					
					// Show success message
					alert('Pembayaran berhasil!\nTotal: Rp ' + total.toLocaleString('id-ID') + '\nUang Diterima: Rp ' + paid.toLocaleString('id-ID') + '\nKembalian: Rp ' + (paid - total).toLocaleString('id-ID'));
					
					// Open receipt
					window.open('print_struk.php?order_id='+data.orderId, '_blank');
					
					// Clear cart and form
					cart.length = 0;
					document.getElementById('table-number').value = '';
					document.getElementById('customer-name').value = '';
					document.getElementById('modal-table').value = '';
					document.getElementById('modal-customer').value = '';
					document.getElementById('modal-paid').value = '';
					renderCart();
				} else {
					alert('Terjadi kesalahan saat memproses pembayaran.');
				}
			}catch(e){ 
				console.error(e); 
				alert('Terjadi kesalahan: ' + e.message); 
			}
		}
	</script>
</body>
</html>
