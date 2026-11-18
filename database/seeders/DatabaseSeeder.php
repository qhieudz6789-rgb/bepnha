<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Notifications\OrderStatusChanged;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ── Tài khoản (role không nằm trong $fillable nên gán bằng forceFill) ──
        (new User)->forceFill([
            'name' => 'Quản trị viên', 'email' => 'admin@bepnha.vn', 'phone' => '0900000000',
            'password' => '123456', 'role' => 'admin',
        ])->save();

        $an = User::create([
            'name' => 'Nguyễn Văn An', 'email' => 'an@gmail.com', 'phone' => '0912345678',
            'address' => '79 Hồ Tùng Mậu, Cầu Giấy, Hà Nội', 'password' => '123456',
        ]);
        $binh = User::create([
            'name' => 'Trần Thị Bình', 'email' => 'binh@gmail.com', 'phone' => '0987654321',
            'address' => '12 Xuân Thuỷ, Cầu Giấy, Hà Nội', 'password' => '123456',
        ]);
        $cuong = User::create([
            'name' => 'Lê Minh Cường', 'email' => 'cuong@gmail.com', 'phone' => '0934567890',
            'address' => '45 Trần Duy Hưng, Cầu Giấy, Hà Nội', 'password' => '123456',
        ]);

        // ── Thực đơn: [tên, mô tả, giá, giá KM, tồn kho, nổi bật, mã ảnh Pexels] ──
        $this->command?->info('Đang tải ảnh món ăn từ Pexels (lần đầu mất khoảng 30 giây)...');
        $menu = [
            ['Món chính', '🍛', [
                ['Cơm gà xối mỡ', 'Gà chiên giòn rụm, cơm chiên vàng, kèm dưa góp.', 45000, 39000, 30, true, 12916867],
                ['Cơm sườn nướng', 'Sườn cốt lết nướng than hoa, mỡ hành, rau dưa.', 50000, null, 25, true, 33999512],
                ['Bún chả Hà Nội', 'Chả viên và chả miếng nướng, nước chấm chua ngọt.', 45000, null, 40, false, 3858270],
                ['Cơm rang dưa bò', 'Cơm rang tơi, dưa cải chua, thịt bò mềm.', 55000, 49000, 20, false, 4940832],
            ]],
            ['Món nước', '🍜', [
                ['Phở bò tái', 'Nước dùng ninh xương 12 tiếng, bánh phở mềm, bò tái.', 50000, null, 35, true, 29305882],
                ['Bún bò Huế', 'Đậm vị sả ớt, giò heo, chả cua.', 55000, null, 20, false, 29305560],
                ['Mì vằn thắn', 'Sủi cảo nhân tôm thịt, nước dùng thanh ngọt.', 45000, 40000, 4, false, 34839267],
            ]],
            ['Ăn vặt', '🥟', [
                ['Nem rán (5 cái)', 'Nem cuốn tay, chiên giòn, kèm rau sống.', 35000, null, 50, true, 4985525],
                ['Khoai tây chiên', 'Khoai chiên giòn, rắc muối, kèm tương cà.', 25000, null, 60, false, 4109234],
                ['Gỏi cuốn tôm thịt', 'Tôm, thịt luộc, bún, rau thơm cuộn bánh tráng, chấm tương đậu.', 30000, 25000, 0, false, 6646031], // hết hàng để thử
            ]],
            ['Đồ uống', '🧋', [
                ['Trà chanh giã tay', 'Chanh tươi giã tay, thơm mát.', 20000, null, 100, false, 792613],
                ['Trà sữa trân châu', 'Trà đen đậm, trân châu đường đen.', 30000, 25000, 80, true, 27126830],
                ['Nước cam ép', 'Cam ép nguyên chất, không đường.', 30000, null, 40, false, 158053],
            ]],
            ['Tráng miệng', '🍮', [
                ['Chè thập cẩm', 'Đậu xanh, đậu đỏ, thạch, nước cốt dừa, đá bào.', 30000, null, 25, false, 36905211],
                ['Xôi xoài nếp cẩm', 'Nếp cẩm dẻo thơm, xoài chín ngọt, nước cốt dừa béo.', 35000, null, 3, false, 7361018],
            ]],
        ];

        foreach ($menu as [$categoryName, $icon, $dishes]) {
            $category = Category::create(['name' => $categoryName, 'icon' => $icon]);
            foreach ($dishes as [$name, $desc, $price, $sale, $stock, $featured, $photoId]) {
                $category->products()->create([
                    'name' => $name, 'description' => $desc, 'price' => $price,
                    'sale_price' => $sale, 'stock' => $stock, 'is_featured' => $featured,
                    'image' => $this->photo($photoId),
                ]);
            }
        }

        $p = Product::all()->keyBy('name');

        // ── Đơn hàng mẫu rải trong 7 ngày để biểu đồ admin có dữ liệu ──
        // [số ngày trước, khách, trạng thái, [[tên món, số lượng], ...]]
        // [số ngày trước, khách, trạng thái, [[tên món, số lượng], ...], cách thanh toán]
        $orders = [
            [6, $an, 'delivered', [['Phở bò tái', 2], ['Trà chanh giã tay', 2]], 'cod'],
            [6, $binh, 'delivered', [['Cơm gà xối mỡ', 1], ['Trà sữa trân châu', 1]], 'momo'],
            [5, $cuong, 'delivered', [['Bún chả Hà Nội', 3], ['Nem rán (5 cái)', 1]], 'vietqr'],
            [4, $an, 'delivered', [['Cơm sườn nướng', 1], ['Nem rán (5 cái)', 2], ['Nước cam ép', 1]], 'momo'],
            [4, $binh, 'cancelled', [['Bún bò Huế', 1]], 'cod'],
            [3, $binh, 'delivered', [['Phở bò tái', 1], ['Chè thập cẩm', 2]], 'cod'],
            [2, $cuong, 'delivered', [['Cơm gà xối mỡ', 2], ['Trà sữa trân châu', 2]], 'cod'],
            [2, $an, 'delivered', [['Trà sữa trân châu', 3]], 'vietqr'],
            [1, $binh, 'delivering', [['Cơm rang dưa bò', 1], ['Trà chanh giã tay', 1]], 'cod'],
            [1, $cuong, 'preparing', [['Mì vằn thắn', 2]], 'momo'],
            [0, $an, 'confirmed', [['Bún chả Hà Nội', 1], ['Khoai tây chiên', 1]], 'vietqr'],
            [0, $binh, 'pending', [['Phở bò tái', 1], ['Xôi xoài nếp cẩm', 1]], 'momo'],
        ];
        $shippers = [['Trần Văn Hùng', '0977111222'], ['Phạm Đức Long', '0966333444']];

        foreach ($orders as $i => [$daysAgo, $user, $status, $lines, $method]) {
            $at = now()->subDays($daysAgo)->setTime(10 + $i % 9, 15 + $i);
            // COD trả tiền khi giao xong; online thì đã trả trừ đơn còn chờ xác nhận / đã huỷ
            $paid = $method === 'cod' ? $status === 'delivered' : ! in_array($status, ['pending', 'cancelled'], true);
            [$shipperName, $shipperPhone] = in_array($status, ['delivering', 'delivered'], true) ? $shippers[$i % 2] : [null, null];

            $order = $user->orders()->create([
                'customer_name' => $user->name, 'phone' => $user->phone,
                'address' => $user->address, 'status' => $status,
                'payment_method' => $method, 'payment_status' => $paid ? 'paid' : 'unpaid',
                'shipper_name' => $shipperName, 'shipper_phone' => $shipperPhone,
            ]);
            $total = 0;
            foreach ($lines as [$name, $qty]) {
                $product = $p[$name];
                $order->items()->create([
                    'product_id' => $product->id, 'product_name' => $product->name,
                    'price' => $product->finalPrice(), 'quantity' => $qty,
                ]);
                $total += $product->finalPrice() * $qty;
            }
            $order->forceFill([
                'total_price' => $total, 'created_at' => $at, 'updated_at' => $at,
                'paid_at' => $paid ? $at->copy()->addMinutes($method === 'cod' ? 60 : 1) : null,
                'payment_ref' => $paid && $method === 'momo' ? "BEPNHA{$order->id}_SEED" : null,
            ])->save();

            // Lịch sử thông báo cho từng bước đơn đã đi qua (cách nhau 15 phút)
            $steps = $status === 'cancelled'
                ? ['pending', 'cancelled']
                : array_slice(Order::FLOW, 0, array_search($status, Order::FLOW, true) + 1);
            foreach ($steps as $k => $event) {
                $time = $at->copy()->addMinutes(15 * $k);
                $user->notifications()->create([
                    'id' => (string) Str::uuid(),
                    'type' => OrderStatusChanged::class,
                    'data' => (new OrderStatusChanged($order, $event))->toArray($user),
                    'read_at' => $daysAgo > 0 ? $time : null, // đơn hôm nay → chưa đọc (chuông có số)
                    'created_at' => $time,
                    'updated_at' => $time,
                ]);
            }
        }

        // ── Đánh giá mẫu (chỉ cho món nằm trong đơn "Đã giao" của khách đó) ──
        $reviews = [
            [$an, 'Phở bò tái', 5, 'Nước dùng đậm đà, bò mềm. Sẽ đặt lại!'],
            [$an, 'Nem rán (5 cái)', 4, 'Giòn, nóng hổi, nhân hơi ít.'],
            [$an, 'Trà sữa trân châu', 5, 'Trân châu dẻo, ngọt vừa.'],
            [$binh, 'Cơm gà xối mỡ', 5, 'Gà giòn, cơm ngon, giao nhanh.'],
            [$binh, 'Phở bò tái', 4, 'Ngon, nhưng hơi ít hành.'],
            [$binh, 'Chè thập cẩm', 5, 'Mát lạnh, ngọt vừa, rất đáng tiền.'],
            [$cuong, 'Bún chả Hà Nội', 5, 'Chuẩn vị Hà Nội, chả thơm mùi than.'],
            [$cuong, 'Trà sữa trân châu', 4, null],
        ];
        foreach ($reviews as [$user, $name, $rating, $comment]) {
            Review::create([
                'user_id' => $user->id, 'product_id' => $p[$name]->id,
                'rating' => $rating, 'comment' => $comment,
            ]);
        }

        // ── Chat mẫu với CSKH (tin của Bình chưa đọc → trang CSKH của admin có số) ──
        $admin = User::where('role', 'admin')->first();
        $chats = [
            [$an, false, 'Cho mình hỏi quán có giao đến Cầu Giấy không ạ?', 50, true],
            [$an, true, 'Dạ có ạ! Bếp Nhà giao miễn phí toàn quận Cầu Giấy, khoảng 20–30 phút ạ.', 48, true],
            [$an, false, 'Ok cảm ơn quán nhé 👍', 47, true],
            [$binh, false, 'Shop ơi đơn của mình bao giờ giao tới vậy?', 3, false],
        ];
        foreach ($chats as [$user, $isStaff, $body, $minutesAgo, $read]) {
            $time = now()->subMinutes($minutesAgo);
            $user->chatMessages()->forceCreate([ // forceCreate: giữ created_at mẫu
                'sender_id' => $isStaff ? $admin->id : $user->id, 'is_staff' => $isStaff, 'body' => $body,
                'read_at' => $read ? $time : null, 'created_at' => $time, 'updated_at' => $time,
            ]);
        }
    }

    /**
     * Ảnh món (nguồn Pexels — miễn phí, không cần ghi công).
     * Tải về storage/app/public/products để demo không cần mạng; tải lỗi thì dùng thẳng link ảnh.
     */
    private function photo(int $id): string
    {
        $url = "https://images.pexels.com/photos/{$id}/pexels-photo-{$id}.jpeg?auto=compress&cs=tinysrgb&w=800";
        $path = "products/pexels-{$id}.jpg";

        if (Storage::disk('public')->exists($path)) {
            return $path; // đã tải ở lần cài trước
        }

        try {
            $response = Http::timeout(20)->withUserAgent('BepNhaShop/1.0 (student project)')->get($url);
            if ($response->successful() && str_starts_with((string) $response->header('Content-Type'), 'image/')) {
                Storage::disk('public')->put($path, $response->body());

                return $path;
            }
        } catch (\Throwable $e) {
            $this->command?->warn("  Không tải được ảnh {$id} ({$e->getMessage()}) → dùng link trực tiếp.");
        }

        return $url;
    }
}
