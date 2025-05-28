import Request from '@/axios/request'

export function getComposeVideos(params) {
  return Request.get('compose-videos', { params })
}

// export function createComposeVideo() {
//   return Request.get('composeVideos/create')
// }

// export function storeComposeVideo(data) {
//   return Request.post('composeVideos', data)
// }

// export function updateComposeVideo(id, data) {
//   return Request.put(`composeVideos/${id}`, data)
// }

// export function editComposeVideo(id) {
//   return Request.get(`composeVideos/${id}/edit`)
// }

export function destroyComposeVideo(id) {
  return Request.delete(`compose-videos/${id}`)
}
