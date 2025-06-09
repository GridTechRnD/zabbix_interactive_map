document.addEventListener("DOMContentLoaded", () => {
    const statusFilter = document.getElementById("status-filter");
    const menu = document.getElementById("menu");

    statusFilter.addEventListener("change", () => {
        const selectedValue = statusFilter.value;
        const selectedText = statusFilter.options[statusFilter.selectedIndex].text;

        const existingOption = document.querySelector(".selected-option");
        if (existingOption) {
            existingOption.remove();
        }

        if (selectedValue !== "") {
            const selectedOptionDiv = document.createElement("div");
            selectedOptionDiv.className = "selected-option";

            const removeButton = document.createElement("button");
            removeButton.className = "remove-button";
            removeButton.textContent = "✖";
            removeButton.title = "Remove selection";

            removeButton.addEventListener("click", () => {
                selectedOptionDiv.remove();
                statusFilter.value = "";
            });

            const optionText = document.createElement("span");
            optionText.textContent = selectedText;

            selectedOptionDiv.appendChild(removeButton);
            selectedOptionDiv.appendChild(optionText);

            menu.appendChild(selectedOptionDiv);
        }
    });
});