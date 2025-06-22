// Color Sets for Each Level
var colors = {
  A1: {
    background: "rgb(252, 162, 162)",
    card: "rgb(255, 106, 106)",
    sections: "rgb(238, 75, 75)"
  },
  A2: {
    background: "rgb(187, 225, 206)",
    card: "rgb(106, 241, 169)",
    sections: "rgb(0, 213, 110)"
  },
  B1: {
    background: "rgb(243, 208, 169)",
    card: "rgb(249, 180, 101)",
    sections: "rgb(242, 133, 24)"
  },
  B2: {
    background: "rgb(178, 194, 238)",
    card: "rgb(102, 137, 236)",
    sections: "rgb(13, 65, 208)"
  },
  Gespräch: {
    background: "rgb(178, 194, 238)",
    card: "rgb(102, 137, 236)",
    sections: "rgb(13, 65, 208)"
  }
};

// DOM References
var stufeElement = document.getElementById("stufe");
var bodyElement = document.body;
var cardBox = document.getElementById("cardBox");
var titleBox = document.getElementById("titleBox");
var separatorBoxes = document.querySelectorAll(".separatorBox");

// Apply colors based on stufe text
var stufe = stufeElement.textContent;
if (colors[stufe]) {
  var scheme = colors[stufe];
  bodyElement.style.backgroundColor = scheme.background;
  cardBox.style.backgroundColor = scheme.card;
  titleBox.style.backgroundColor = scheme.sections;

for (var i = 0; i < separatorBoxes.length; i++) {
  separatorBoxes[i].style.backgroundColor = scheme.sections;
}

}

// Adjust font sizes for TAGE
var tageElement = document.getElementById("tageCell");
var tageLines = (tageElement.innerHTML.match(/<br>(?!\s*$)/g) || []).length + 1;
if (tageLines === 2) {
  tageElement.style.fontSize = "14px";
} else if (tageLines === 3) {
  tageElement.style.fontSize = "13px";
  tageElement.style.lineHeight = "1";
} else if (tageLines > 3) {
  tageElement.style.fontSize = "12px";
  tageElement.style.lineHeight = "0.9";
} else {
  tageElement.style.fontSize = "15px";
  tageElement.style.lineHeight = "1";
}

// Adjust font sizes for ZEITEN
var zeitenElement = document.getElementById("zeitenCell");
var zeitenLines = (zeitenElement.innerHTML.match(/<br>(?!\s*$)/g) || []).length + 1;
if (zeitenLines === 2) {
  zeitenElement.style.fontSize = "14px";
} else if (zeitenLines === 3) {
  zeitenElement.style.fontSize = "13px";
  zeitenElement.style.lineHeight = "1";
} else if (zeitenLines > 3) {
  zeitenElement.style.fontSize = "12px";
  zeitenElement.style.lineHeight = "0.9";
} else {
  zeitenElement.style.fontSize = "15px";
  zeitenElement.style.lineHeight = "1";
}

    // Additional logic: Auto-generate zeitenCell content only if empty
    var tageCellElement = document.getElementById("tageCell");
    var zeitenCellElement = document.getElementById("zeitenCell");

    if (tageCellElement && zeitenCellElement && zeitenCellElement.textContent.trim() === "") {
      var tageLinesList = tageCellElement.innerHTML.split(/<br\s*\/?>/ig);
      var generatedZeitenLines = [];

      for (var j = 0; j < tageLinesList.length; j++) {
        generatedZeitenLines.push("00:00-00:00");
      }

      zeitenCellElement.innerHTML = generatedZeitenLines.join("<br>");
    }
