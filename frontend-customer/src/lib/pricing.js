export function computeNights(checkIn, checkOut) {
  if (!checkIn || !checkOut) return 0;
  const start = new Date(`${checkIn}T00:00:00`);
  const end = new Date(`${checkOut}T00:00:00`);
  const diff = (end - start) / 86_400_000;
  return Number.isFinite(diff) && diff > 0 ? Math.round(diff) : 0;
}

export function roomPricePerNight(room) {
  return Number(room?.room_type?.base_price ?? 0);
}

export function calculateBookingPricing({ rooms, checkIn, checkOut, coupon }) {
  const nights = computeNights(checkIn, checkOut);
  const items = rooms.map((room) => {
    const pricePerNight = roomPricePerNight(room);
    return {
      roomId: room.id,
      roomNumber: room.room_number,
      roomType: room.room_type?.name ?? "Room",
      imageUrl: room.image_url ?? room.room_type?.image_url ?? null,
      pricePerNight,
      nights,
      subtotal: Number((pricePerNight * nights).toFixed(2)),
    };
  });

  const baseTotal = Number(
    items.reduce((sum, item) => sum + item.subtotal, 0).toFixed(2)
  );

  let discount = 0;
  if (coupon?.valid) {
    if (coupon.discount_type === "percentage") {
      discount = Number(((baseTotal * coupon.discount_value) / 100).toFixed(2));
    } else {
      discount = Math.min(baseTotal, Number(coupon.discount_value));
    }
  }

  const totalAfterDiscount = Number(Math.max(baseTotal - discount, 0).toFixed(2));

  const depositRate = 20; // Cambodia guests
  const depositAmount = Number(
    (totalAfterDiscount * (depositRate / 100)).toFixed(2)
  );

  return {
    nights,
    items,
    baseTotal,
    discount,
    total: totalAfterDiscount,
    depositRate,
    depositAmount,
  };
}