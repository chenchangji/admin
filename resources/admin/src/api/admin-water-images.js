import Request from '@/axios/request'

export function getWaterImages(params) {
  return Request.get('admin-water-images', { params })
}

export function createWaterImage() {
  return Request.get('admin-water-images/create')
}

export function storeWaterImage(data) {
  return Request.post('admin-water-images', data)
}

export function updateWaterImage(id, data) {
  return Request.put(`admin-water-images/${id}`, data)
}

export function editWaterImage(id) {
  return Request.get(`admin-water-images/${id}/edit`)
}

export function destroyWaterImage(id) {
  return Request.delete(`admin-water-images/${id}`)
}

export function getAdminWaterImageList(params) {
  return Request.get('admin-water-images/list', { params })
}
