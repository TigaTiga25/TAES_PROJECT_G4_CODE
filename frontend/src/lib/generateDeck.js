export default function generateDeck() {
  const suits = ['c', 'e', 'p', 'o'] 
  const values = ['1', '2', '3', '4', '5', '6', '7', '11', '12', '13']
  const deck = []

  for (const suit of suits) {
    for (const value of values) {
      deck.push({ suit, value })
    }
  }

  return deck
}
