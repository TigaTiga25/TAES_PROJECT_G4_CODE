const imageModules = import.meta.glob('@/assets/cards/*.png', { eager: true })

const images = {}
for (const path in imageModules) {
  // Extrai o nome do ficheiro (ex: c1.png -> c1)
  const name = path.split('/').pop().replace('.png', '')
  // Cada entrada aponta para o caminho correto
  images[name] = imageModules[path].default || imageModules[path]
}

export default images
