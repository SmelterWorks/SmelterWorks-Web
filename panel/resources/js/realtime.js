function appendMessage(container, message) {
    if (container.querySelector(`[data-message-id="${message.id}"]`)) {
        return;
    }

    const node = document.createElement('div');
    node.dataset.messageId = String(message.id);
    node.className = 'rounded-lg border border-border bg-zinc-900/40 p-3';
    node.innerHTML = `
        <p class="text-xs text-zinc-500">${message.user?.name ?? 'User'} · just now</p>
        <p class="mt-1 text-sm text-zinc-200"></p>
    `;
    node.querySelector('p:last-child').textContent = message.body;
    container.appendChild(node);
    container.scrollTop = container.scrollHeight;
}

function pollMessages(root, url, lastId) {
    const container = root.querySelector('#chat-messages, #ticket-messages');

    if (!container) {
        return lastId;
    }

    fetch(`${url}?after=${lastId}`, {
        headers: { Accept: 'application/json' },
    })
        .then((response) => response.json())
        .then((payload) => {
            for (const message of payload.messages ?? []) {
                appendMessage(container, message);
                lastId = Math.max(lastId, message.id);
            }
        })
        .catch(() => {});

    return lastId;
}

document.addEventListener('DOMContentLoaded', () => {
    const chatRoom = document.getElementById('chat-room');

    if (chatRoom) {
        let lastId = Number(chatRoom.dataset.lastId || 0);
        const url = chatRoom.dataset.pollUrl;

        setInterval(() => {
            lastId = pollMessages(chatRoom, url, lastId);
        }, 3000);
    }

    const ticketThread = document.getElementById('ticket-thread');

    if (ticketThread) {
        let lastId = Number(ticketThread.dataset.lastId || 0);
        const ticketId = ticketThread.dataset.ticketId;
        const url = `/support/tickets/${ticketId}/poll`;

        setInterval(() => {
            lastId = pollMessages(ticketThread, url, lastId);
        }, 4000);
    }
});
