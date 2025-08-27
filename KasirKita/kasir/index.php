<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'kasir') {
	// header("Location: ../login.php?role=kasir");
	// exit;
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
			<a href="../index.php" class="btn btn-outline-secondary"><i class="fas fa-home me-2"></i>Home</a>
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
									<button class="btn btn-sm btn-primary" <?php echo $disabled?'disabled':''; ?> onclick='addToCart(<?php echo json_encode(["id"=>$m['id'],"name"=>$m['nama_menu'],"price"=>(int)$m['harga'],"stok"=>$hasStock?(int)$m['stok']:null]); ?>)'><i class="fas fa-plus me-1"></i>Tambah</button>
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
					<h5 class="modal-title">Pembayaran Tunai</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
				</div>
				<div class="modal-body">
					<div class="mb-3">
						<label class="form-label">Total</label>
						<input type="text" id="modal-total" class="form-control" readonly>
					</div>
					<div class="mb-3">
						<label class="form-label">Uang Diterima</label>
						<input type="number" id="modal-paid" class="form-control" placeholder="Masukkan nominal uang">
					</div>
					<div class="mb-1 text-end">
						<strong>Kembalian: <span id="modal-change">Rp 0</span></strong>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
					<button type="button" class="btn btn-success" onclick="confirmCash()">Konfirmasi</button>
				</div>
			</div>
		</div>
	</div>

	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
	<script>
		const cart = [];
		function addToCart(item){
			// Enforce stock
			if(item.stok !== null && item.stok !== undefined){
				const used = cart.find(i => i.id===item.id)?.qty || 0;
				if(used >= item.stok){ alert('Stok tidak cukup'); return; }
			}
			const existing = cart.find(i => i.id === item.id);
			if(existing){ existing.qty += 1; } else { cart.push({...item, qty:1}); }
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
		function renderCart(){
			let html=''; let total=0;
			cart.forEach(i=>{
				total += i.price*i.qty;
				html += `<div class="d-flex justify-content-between align-items-center mb-2">
					<div>
						<div class="fw-semibold">${i.name}</div>
						<small class="text-muted">Rp ${i.price.toLocaleString('id-ID')}${i.stok!=null?` • Stok: ${i.stok - i.qty}`:''}</small>
					</div>
					<div class="d-flex align-items-center gap-2">
						<button class="btn btn-sm btn-outline-secondary" onclick="changeQty(${i.id},-1)">-</button>
						<span>${i.qty}</span>
						<button class="btn btn-sm btn-outline-secondary" onclick="changeQty(${i.id},1)">+</button>
					</div>
				</div>`
			});
			document.getElementById('cart-list').innerHTML = html || '<div class="text-muted">Keranjang kosong</div>';
			document.getElementById('cart-total').innerText = 'Rp ' + total.toLocaleString('id-ID');
			const modalTotal = document.getElementById('modal-total'); if(modalTotal){ modalTotal.value = 'Rp ' + total.toLocaleString('id-ID'); }
			const paidInput = document.getElementById('modal-paid');
			if(paidInput){
				paidInput.oninput = () => {
					const paid = parseInt(paidInput.value||0);
					const change = Math.max(0, paid - total);
					document.getElementById('modal-change').innerText = 'Rp ' + change.toLocaleString('id-ID');
				};
			}
		}

		async function confirmCash(){
			let total = 0; cart.forEach(i=> total += i.price*i.qty);
			const paid = parseInt(document.getElementById('modal-paid').value||0);
			if(paid < total){ return alert('Uang diterima kurang dari total.'); }
			const table = document.getElementById('table-number').value || 0;
			const customer = document.getElementById('customer-name').value || 'Tamu';
			try{
				const res = await fetch('create_cash_order.php', {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({items:cart, table, customer, paid})});
				const data = await res.json();
				window.open('print_struk.php?order_id='+data.orderId, '_blank');
				window.location.reload();
			}catch(e){ console.error(e); alert('Terjadi kesalahan.'); }
		}
	</script>
</body>
</html>
