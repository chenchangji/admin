import Request from '@/axios/request'

export function getAdminMaterials(params) {
  return Request.get('admin-materials', { params })
}

export function createAdminMaterial() {
  return Request.get('admin-materials/create')
}

export function storeAdminMaterial(data) {
  return Request.post('admin-materials', data)
}

export function updateAdminMaterial(id, data) {
  return Request.put(`admin-materials/${id}`, data)
}

export function editAdminMaterial(id) {
  return Request.get(`admin-materials/${id}/edit`)
}

export function destroyAdminMaterial(id) {
  return Request.delete(`admin-materials/${id}`)
}

export function exportAdminMaterials(params) {
  return Request.get('admin-materials/export', { params })
}

export function getCountByClass() {
  return Request.get('admin-materials/getCountByClass')
}

export function getCountByProduct() {
  return Request.get('admin-materials/getCountByProduct')
}

export function getVideoCount(params) {
  return Request.get(`admin-materials/getVideoCount`, {params})
}

export function updateVideoScore(data) {
  return Request.post(`admin-materials/updateScore`, data)
}