const imageModules = import.meta.glob('@/assets/cards/*.png', { eager: true })
const images = {}

//Função para extrair o nome de cada carta a partir do caminho e armazenar no objeto images
for (const path in imageModules) {
  const name = path.split('/').pop().replace('.png', '')
  images[name] = imageModules[path].default || imageModules[path]
}

export default images
