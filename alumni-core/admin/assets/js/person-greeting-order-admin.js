document.addEventListener('DOMContentLoaded', function () {
	var draggedItem = null;

	document.querySelectorAll('.alumni-person-greeting-sortable').forEach(function (list) {
		list.addEventListener('dragstart', function (event) {
			draggedItem = event.target.closest('.alumni-person-greeting-sortable-item');
			if (!draggedItem) return;
			draggedItem.classList.add('is-dragging');
			event.dataTransfer.effectAllowed = 'move';
		});

		list.addEventListener('dragend', function () {
			if (draggedItem) draggedItem.classList.remove('is-dragging');
			draggedItem = null;
		});

		list.addEventListener('dragover', function (event) {
			event.preventDefault();
			if (!draggedItem) return;

			var target = event.target.closest('.alumni-person-greeting-sortable-item');
			if (!target || target === draggedItem) return;

			var rect = target.getBoundingClientRect();
			if (event.clientY < rect.top + rect.height / 2) {
				list.insertBefore(draggedItem, target);
			} else {
				list.insertBefore(draggedItem, target.nextSibling);
			}
		});
	});
});
