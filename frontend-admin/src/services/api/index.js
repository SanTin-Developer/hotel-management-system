import apiClient from "@/lib/apiClient";

export async function list(endpoint, params = {}) {
  const res = await apiClient.get(endpoint, { params });
  const { data, meta } = res.data ?? {};
  return { items: data ?? [], meta };
}

export async function getOne(endpoint) {
  const res = await apiClient.get(endpoint);
  return res.data?.data;
}

export async function create(endpoint, payload) {
  const res = await apiClient.post(endpoint, payload);
  return res.data?.data ?? res.data;
}

export async function update(endpoint, payload) {
  const res = await apiClient.put(endpoint, payload);
  return res.data?.data ?? res.data;
}

export async function remove(endpoint) {
  const res = await apiClient.delete(endpoint);
  return res.data;
}

export async function action(endpoint, method = "post", payload) {
  const res =
    method === "put"
      ? await apiClient.put(endpoint, payload)
      : await apiClient.post(endpoint, payload);
  return res.data?.data ?? res.data;
}