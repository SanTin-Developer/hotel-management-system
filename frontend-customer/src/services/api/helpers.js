export function toApiList(response) {
  const { data, meta } = response?.data ?? {};
  return { items: Array.isArray(data) ? data : [], meta };
}

export function toApiItem(response) {
  return response?.data?.data ?? response?.data;
}

export function toErrorResponse(error) {
  if (error?.response?.data) return error.response.data;
  if (error?.message) return { message: error.message };
  return { message: "Something went wrong." };
}