import Request from '@/axios/request'

export function getAdminActors(params) {
  return Request.get('admin-actors', { params })
}

export function createAdminActor() {
  return Request.get('admin-actors/create')
}

export function storeAdminActor(data) {
  return Request.post('admin-actors', data)
}

export function updateAdminActor(id, data) {
  return Request.put(`admin-actors/${id}`, data)
}

export function editAdminActor(id) {
  return Request.get(`admin-actors/${id}/edit`)
}

export function destroyAdminActor(id) {
  return Request.delete(`admin-actors/${id}`)
}

export function getAdminActorList(params) {
  return Request.get('admin-actors/list', { params })
}
