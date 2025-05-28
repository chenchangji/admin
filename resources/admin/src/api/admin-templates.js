import Request from '@/axios/request'

export function getAdminTemplates(params) {
  return Request.get('admin-templates', { params })
}

export function createAdminTemplate() {
  return Request.get('admin-templates/create')
}

export function storeAdminTemplate(data) {
  return Request.post('admin-templates', data)
}

export function updateAdminTemplate(id, data) {
  return Request.put(`admin-templates/${id}`, data)
}

export function editAdminTemplate(id) {
  return Request.get(`admin-templates/${id}/edit`)
}

export function destroyAdminTemplate(id) {
  return Request.delete(`admin-templates/${id}`)
}

export function generateTemplateVideo(params) {
  return Request.post(`admin-templates/generate-video`, params)
}