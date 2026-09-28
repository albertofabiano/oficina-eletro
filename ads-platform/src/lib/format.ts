const integer = new Intl.NumberFormat("pt-BR");
const percent = new Intl.NumberFormat("pt-BR", { style: "percent", minimumFractionDigits: 2, maximumFractionDigits: 2 });
const signedPercent = new Intl.NumberFormat("pt-BR", {
  style: "percent",
  maximumFractionDigits: 1,
  signDisplay: "exceptZero",
});

export const formatInteger = (value: number) => integer.format(value);
export const formatPercent = (ratio: number) => percent.format(ratio);
export const formatChange = (ratio: number) => signedPercent.format(ratio);
